<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Http;

class EsmsAfricaProvider implements SmsProviderInterface
{
	public function send(string $phone, string $message): SmsResponse
	{
		$token = config('services.sms.token');

		if (! is_string($token) || trim($token) === '') {
			return SmsResponse::failure('ESMS_TOKEN is not configured.');
		}

		$url = rtrim(
			config('services.sms.api_url', 'https://sms.esmsafrica.io'),
			'/'
		).'/api/messages/send';

		$response = Http::withToken($token)
			->acceptJson()
			->asJson()
			->post($url, [
				'to' => $phone,
				'text' => $message,
			]);

		if ($response->successful()) {
			$data = $response->json();

			return SmsResponse::success($data['id'] ?? null);
		}

		return SmsResponse::failure(
			'eSMS HTTP '.$response->status().': '.substr((string) $response->body(), 0, 500)
		);
	}

	public function name(): string
	{
		return 'esms_africa';
	}
}
