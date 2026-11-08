<?php

namespace App\Services\Sms;

use App\Support\BeninPhoneNumber;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class WatiWhatsAppProvider implements WhatsAppProviderInterface, SupportsDocuments
{
    public function send(string $phone, string $message, ?array $metadata = null): SmsResponse
    {
        $configuration = $this->configuration();

        if ($configuration instanceof SmsResponse) {
            return $configuration;
        }

        $to = $this->toWatiNumber($phone);

        if ($to === null) {
            return SmsResponse::failure("Numéro invalide pour WhatsApp : {$phone}");
        }

        $whatsapp = $metadata['whatsapp'] ?? null;

        if (is_array($whatsapp) && ! empty($whatsapp['template'])) {
            return $this->sendTemplate($to, $whatsapp, $configuration);
        }

        return $this->sendText($to, $message, $configuration);
    }

    public function sendDocument(
        string $phone,
        string $link,
        ?string $filename = null,
        ?string $caption = null
    ): SmsResponse {
        $configuration = $this->configuration();

        if ($configuration instanceof SmsResponse) {
            return $configuration;
        }

        $to = $this->toWatiNumber($phone);

        if ($to === null) {
            return SmsResponse::failure("Numéro invalide pour WhatsApp : {$phone}");
        }

        try {
            $response = $this->client($configuration)
                ->post($this->url($configuration, '/api/ext/v3/conversations/messages/fileViaUrl'), array_filter([
                    'target' => $this->conversationTarget($to, $configuration),
                    'file_url' => $link,
                    'caption' => $caption,
                    'is_bot' => true,
                ], fn ($value) => $value !== null));
        } catch (Throwable $exception) {
            return SmsResponse::failure('WATI : '.$exception->getMessage());
        }

        return $this->conversationResponse($response);
    }

    public function name(): string
    {
        return 'wati';
    }

    /**
     * @return array{api_key: string, api_url: string, channel: ?string, timeout: int}|SmsResponse
     */
    private function configuration(): array|SmsResponse
    {
        if (! (bool) config('services.whatsapp.wati.enabled', false)) {
            return SmsResponse::failure('WATI_ENABLED is not enabled.');
        }

        $apiKey = config('services.whatsapp.wati.api_key');

        if (! is_string($apiKey) || trim($apiKey) === '') {
            return SmsResponse::failure('WATI_API_KEY is not configured.');
        }

        $base = rtrim((string) config('services.whatsapp.wati.api_url', 'https://live-mt-server.wati.io'), '/');
        $channel = config('services.whatsapp.wati.channel_number');
        $timeout = max(1, (int) config('services.whatsapp.wati.timeout', 15));

        return [
            'api_key' => $apiKey,
            'api_url' => $base === '' ? 'https://live-mt-server.wati.io' : $base,
            'channel' => is_string($channel) && trim($channel) !== '' ? trim($channel) : null,
            'timeout' => $timeout,
        ];
    }

    /**
     * @param  array{api_key: string, api_url: string, channel: ?string, timeout: int}  $configuration
     */
    private function sendText(string $to, string $message, array $configuration): SmsResponse
    {
        try {
            $response = $this->client($configuration)
                ->post($this->url($configuration, '/api/ext/v3/conversations/messages/text'), [
                    'target' => $this->conversationTarget($to, $configuration),
                    'text' => $message,
                    'is_bot' => true,
                ]);
        } catch (Throwable $exception) {
            return SmsResponse::failure('WATI : '.$exception->getMessage());
        }

        return $this->conversationResponse($response);
    }

    /**
     * @param  array<string, mixed>  $whatsapp
     * @param  array{api_key: string, api_url: string, channel: ?string, timeout: int}  $configuration
     */
    private function sendTemplate(string $to, array $whatsapp, array $configuration): SmsResponse
    {
        $logicalName = (string) $whatsapp['template'];
        $base = $this->templateConfiguration($logicalName);
        $language = (string) ($whatsapp['language'] ?? $base['language'] ?? 'fr');
        $localized = $this->localizedTemplateConfiguration($base, $language);

        $templateName = (string) ($localized['name'] ?? $base['name'] ?? $logicalName);
        $bodyParameterNames = $this->stringList($localized['body_parameter_names'] ?? $base['body_parameter_names'] ?? []);
        $urlParameterName = $this->stringValue($localized['url_parameter_name'] ?? $base['url_parameter_name'] ?? null);
        $bodyParameters = $this->stringList($whatsapp['body_parameters'] ?? []);
        $urlParameter = $this->stringValue($whatsapp['url_parameter'] ?? null);
        $customParameters = $this->normaliseCustomParameters($whatsapp['custom_params'] ?? []);

        if ($bodyParameterNames === [] || ($urlParameter !== null && $urlParameterName === null)) {
            $remote = $this->remoteTemplateConfiguration($templateName, $configuration);

            if ($bodyParameterNames === []) {
                $bodyParameterNames = $remote['body_parameter_names'] ?? [];
            }

            if ($urlParameter !== null && $urlParameterName === null) {
                $urlParameterName = $remote['url_parameter_name'] ?? null;
            }
        }

        if ($customParameters === []) {
            if ($bodyParameters !== [] && $bodyParameterNames === []) {
                return SmsResponse::failure(
                    "WATI : les noms des paramètres du modèle {$templateName} sont introuvables. Configure WATI_EVALUATION_BODY_PARAMETERS ou rends le modèle visible dans l'espace WATI."
                );
            }

            if ($bodyParameters !== [] && count($bodyParameters) !== count($bodyParameterNames)) {
                return SmsResponse::failure(
                    "WATI : le modèle {$templateName} attend ".count($bodyParameterNames)
                    .' paramètre(s) de corps, mais '.count($bodyParameters).' valeur(s) ont été fournies.'
                );
            }

            foreach ($bodyParameterNames as $index => $name) {
                $customParameters[$name] = $bodyParameters[$index];
            }
        }

        if ($urlParameter !== null) {
            if ($urlParameterName === null) {
                return SmsResponse::failure(
                    "WATI : le nom du paramètre du bouton URL du modèle {$templateName} est introuvable."
                );
            }

            $customParameters[$urlParameterName] = $urlParameter;
        }

        $customParameters = $this->formatCustomParameters($customParameters);

        $localMessageId = (string) Str::uuid();
        $recipient = [
            'phone_number' => $to,
            'local_message_id' => $localMessageId,
        ];

        if ($customParameters !== []) {
            $recipient['custom_params'] = $customParameters;
        }

        $payload = [
            'template_name' => $templateName,
            'broadcast_name' => 'corischool_'.$this->broadcastSlug($logicalName).'_'.now()->format('YmdHis').'_'.Str::lower(Str::random(6)),
            'recipients' => [$recipient],
        ];

        if ($configuration['channel'] !== null) {
            $payload['channel'] = $configuration['channel'];
        }

        try {
            $response = $this->client($configuration)
                ->post($this->url($configuration, '/api/ext/v3/messageTemplates/send'), $payload);
        } catch (Throwable $exception) {
            return SmsResponse::failure('WATI : '.$exception->getMessage());
        }

        $data = $response->json();

        if (! $response->successful()) {
            return SmsResponse::failure('WATI HTTP '.$response->status().': '.$this->errorMessage($response));
        }

        if (! is_array($data) || ($data['success'] ?? false) !== true) {
            $error = is_array($data) && isset($data['error']) && is_string($data['error'])
                ? $data['error']
                : 'WATI a refusé le modèle sans préciser de motif.';

            return SmsResponse::failure($error);
        }

        $recipientResult = collect($data['recipients'] ?? [])
            ->first(fn ($item) => is_array($item) && ($item['local_message_id'] ?? null) === $localMessageId);

        if (! is_array($recipientResult)) {
            return SmsResponse::failure('WATI n’a pas confirmé la réception du destinataire.');
        }

        $errors = $this->stringList($recipientResult['errors'] ?? []);

        if ($errors !== []) {
            return SmsResponse::failure('WATI : '.implode('; ', $errors));
        }

        return SmsResponse::success($localMessageId);
    }

    /**
     * @param  array{api_key: string, api_url: string, channel: ?string, timeout: int}  $configuration
     */
    private function conversationResponse(Response $response): SmsResponse
    {
        if (! $response->successful()) {
            return SmsResponse::failure('WATI HTTP '.$response->status().': '.$this->errorMessage($response));
        }

        $data = $response->json();
        $message = is_array($data) ? ($data['message'] ?? null) : null;

        if (! is_array($message)) {
            return SmsResponse::failure('WATI : réponse inattendue.');
        }

        $status = strtolower((string) ($message['status'] ?? ''));

        if ($status === 'failed') {
            return SmsResponse::failure(
                (string) ($message['failed_detail'] ?? 'WATI a signalé un échec d’envoi.')
            );
        }

        $externalId = $this->stringValue($message['local_message_id'] ?? null)
            ?? $this->stringValue($message['id'] ?? null);

        if ($externalId === null) {
            return SmsResponse::failure('WATI : aucun identifiant de message retourné.');
        }

        return SmsResponse::success($externalId);
    }

    /**
     * @param  array{api_key: string, api_url: string, channel: ?string, timeout: int}  $configuration
     * @return array{body_parameter_names: array<int, string>, url_parameter_name: ?string}
     */
    private function remoteTemplateConfiguration(string $templateName, array $configuration): array
    {
        $ttl = max(0, (int) config('services.whatsapp.wati.template_cache_ttl', 300));
        $cacheKey = 'wati.template.'.sha1($configuration['api_url'].'|'.$configuration['channel'].'|'.$templateName);

        if ($ttl > 0) {
            $template = Cache::remember($cacheKey, now()->addSeconds($ttl), fn () => $this->fetchTemplate($templateName, $configuration) ?? []);
        } else {
            $template = $this->fetchTemplate($templateName, $configuration) ?? [];
        }

        if ($template === []) {
            return ['body_parameter_names' => [], 'url_parameter_name' => null];
        }

        $allNames = $this->stringList(collect($template['custom_params'] ?? [])->pluck('name')->all());
        $urlNames = [];

        foreach ($template['buttons'] ?? [] as $button) {
            if (! is_array($button)) {
                continue;
            }

            $parameter = is_array($button['parameter'] ?? null) ? $button['parameter'] : [];
            $mapping = is_array($parameter['button_param_mapping'] ?? null) ? $parameter['button_param_mapping'] : [];
            $paramName = $this->stringValue($mapping['param_name'] ?? null);

            if ($paramName === null) {
                continue;
            }

            $type = strtolower((string) ($button['type'] ?? ''));

            if (str_contains($type, 'url') || filled($parameter['url'] ?? null) || filled($parameter['url_original'] ?? null)) {
                $urlNames[] = $paramName;
            }
        }

        return [
            'body_parameter_names' => array_values(array_diff($allNames, $urlNames)),
            'url_parameter_name' => $urlNames[0] ?? null,
        ];
    }

    /**
     * @param  array{api_key: string, api_url: string, channel: ?string, timeout: int}  $configuration
     * @return array<string, mixed>|null
     */
    private function fetchTemplate(string $templateName, array $configuration): ?array
    {
        $page = 1;
        $seen = 0;
        $total = null;

        do {
            try {
                $response = $this->client($configuration)->get(
                    $this->url($configuration, '/api/ext/v3/messageTemplates'),
                    array_filter([
                        'channel' => $configuration['channel'],
                        'page_number' => $page,
                        'page_size' => 100,
                    ], fn ($value) => $value !== null && $value !== '')
                );
            } catch (Throwable $exception) {
                Log::warning('WATI template lookup failed.', ['template' => $templateName, 'error' => $exception->getMessage()]);

                return null;
            }

            if (! $response->successful()) {
                Log::warning('WATI template lookup failed.', ['template' => $templateName, 'status' => $response->status()]);

                return null;
            }

            $data = $response->json();
            $templates = is_array($data) ? ($data['templates'] ?? []) : [];
            $templates = is_array($templates) ? $templates : [];

            foreach ($templates as $template) {
                if (is_array($template) && ($template['name'] ?? null) === $templateName) {
                    return $template;
                }
            }

            $seen += count($templates);
            $total = is_array($data) && isset($data['total']) ? (int) $data['total'] : $seen;
            $page++;
        } while ($templates !== [] && $seen < $total && $page <= 20);

        return null;
    }

    /**
     * @param  array<string, mixed>  $configuration
     */
    private function client(array $configuration): \Illuminate\Http\Client\PendingRequest
    {
        return Http::withToken($configuration['api_key'])
            ->acceptJson()
            ->asJson()
            ->timeout($configuration['timeout']);
    }

    /**
     * @param  array<string, mixed>  $configuration
     */
    private function url(array $configuration, string $path): string
    {
        return rtrim($configuration['api_url'], '/').$path;
    }

    /**
     * @param  array<string, mixed>  $configuration
     */
    private function conversationTarget(string $to, array $configuration): string
    {
        return $configuration['channel'] !== null
            ? $configuration['channel'].':'.$to
            : $to;
    }

    /**
     * @return array<string, mixed>
     */
    private function templateConfiguration(string $logicalName): array
    {
        $templates = config('services.whatsapp.wati.templates', []);

        return is_array($templates) && is_array($templates[$logicalName] ?? null)
            ? $templates[$logicalName]
            : [];
    }

    /**
     * @param  array<string, mixed>  $base
     * @return array<string, mixed>
     */
    private function localizedTemplateConfiguration(array $base, string $language): array
    {
        $languages = $base['languages'] ?? [];

        return is_array($languages) && is_array($languages[$language] ?? null)
            ? $languages[$language]
            : [];
    }

    /**
     * @return array<string, string>
     */
    private function normaliseCustomParameters(mixed $parameters): array
    {
        if (! is_array($parameters)) {
            return [];
        }

        $normalised = [];

        if (array_is_list($parameters)) {
            foreach ($parameters as $parameter) {
                if (! is_array($parameter)) {
                    continue;
                }

                $name = $this->stringValue($parameter['name'] ?? null);

                if ($name !== null) {
                    $normalised[$name] = (string) ($parameter['value'] ?? '');
                }
            }

            return $normalised;
        }

        foreach ($parameters as $name => $value) {
            if (is_string($name) && $name !== '') {
                $normalised[$name] = is_scalar($value) || $value === null ? (string) $value : json_encode($value);
            }
        }

        return $normalised;
    }

    /**
     * @param  array<string, string>  $parameters
     * @return array<int, array{name: string, value: string}>
     */
    private function formatCustomParameters(array $parameters): array
    {
        $formatted = [];

        foreach ($parameters as $name => $value) {
            if ($name === '') {
                continue;
            }

            $formatted[] = ['name' => (string) $name, 'value' => (string) $value];
        }

        return $formatted;
    }

    /**
     * @return array<int, string>
     */
    private function stringList(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter(array_map(function ($item) {
            if (is_scalar($item) || $item === null) {
                $item = (string) $item;

                return trim($item) === '' ? null : $item;
            }

            return null;
        }, $value), fn ($item) => $item !== null));
    }

    private function stringValue(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function broadcastSlug(string $value): string
    {
        $slug = Str::slug($value);

        return $slug === '' ? 'template' : $slug;
    }

    private function errorMessage(Response $response): string
    {
        $error = $response->json('error') ?? $response->json('message');

        if (is_array($error)) {
            $error = json_encode($error);
        }

        if (is_string($error) && trim($error) !== '') {
            return $error;
        }

        return substr((string) $response->body(), 0, 500);
    }

    private function toWatiNumber(string $phone): ?string
    {
        $e164 = $this->toE164($phone);

        return $e164 === null ? null : ltrim($e164, '+');
    }

    private function toE164(string $phone): ?string
    {
        $countryCode = (string) config('services.sms.country_code', '228');
        $assumeBenin = $countryCode === BeninPhoneNumber::COUNTRY_CODE;

        if ($benin = BeninPhoneNumber::forWhatsApp($phone, $assumeBenin)) {
            return $benin;
        }

        $number = preg_replace('/[^0-9+]/', '', $phone);

        if (str_starts_with($number, '+')) {
            $candidate = $number;
        } elseif (str_starts_with($number, '00')) {
            $candidate = '+'.substr($number, 2);
        } elseif (strlen($number) >= 11 && $countryCode !== '' && str_starts_with($number, $countryCode)) {
            $candidate = '+'.$number;
        } else {
            $candidate = '+'.$countryCode.ltrim($number, '0');
        }

        $candidate = BeninPhoneNumber::withoutNationalPrefix($candidate);

        if (preg_match('/^\+[1-9]\d{7,14}$/', $candidate) !== 1) {
            return null;
        }

        return $candidate;
    }
}
