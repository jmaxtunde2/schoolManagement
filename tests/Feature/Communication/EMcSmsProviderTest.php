<?php

namespace Tests\Feature\Communication;

use App\Services\Sms\EMcSmsProvider;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class EMcSmsProviderTest extends TestCase
{
    private function configureProvider(array $overrides = []): void
    {
        config(array_merge([
            'services.sms.emc.enabled' => true,
            'services.sms.emc.api_url' => 'https://api.e-mc.co/v3',
            'services.sms.emc.api_key' => null,
            'services.sms.emc.username' => 'corisch',
            'services.sms.emc.password' => 'secret',
            'services.sms.emc.sender' => 'CoriSchool',
            'services.sms.emc.timeout' => 15,
            'services.sms.emc.country_code' => '229',
        ], $overrides));
    }

    public function test_it_sends_an_sms_with_basic_auth_and_returns_the_message_id(): void
    {
        $this->configureProvider();

        Http::fake([
            'https://api.e-mc.co/v3/sms' => Http::response([
                'data' => ['success' => true, 'messageId' => 'message-123'],
            ], 200),
        ]);

        $provider = new EMcSmsProvider;
        $response = $provider->send('+22997000000', 'Bonjour depuis CoriSchool');

        $this->assertTrue($response->success);
        $this->assertSame('message-123', $response->externalId);
        $this->assertSame(200, $provider->lastHttpStatus());

        Http::assertSent(fn (HttpRequest $request) => $request->url() === 'https://api.e-mc.co/v3/sms'
            && $request->method() === 'POST'
            && $request->hasHeader('Authorization', 'Basic '.base64_encode('corisch:secret'))
            && $request->hasHeader('Content-Type', 'application/json')
            && $request['from'] === 'CoriSchool'
            && $request['to'] === '2290197000000'
            && $request['content'] === 'Bonjour depuis CoriSchool'
        );
    }

    public function test_it_accepts_a_pre_encoded_api_key(): void
    {
        $this->configureProvider([
            'services.sms.emc.username' => null,
            'services.sms.emc.password' => null,
            'services.sms.emc.api_key' => 'ZmFrZS11c2VyOmZha2UtcGFzcw==',
        ]);

        Http::fake([
            'https://api.e-mc.co/v3/sms' => Http::response([
                'data' => ['success' => true, 'messageId' => 'message-456'],
            ], 200),
        ]);

        $response = (new EMcSmsProvider)->send('97000000', 'Bonjour');

        $this->assertTrue($response->success);

        Http::assertSent(fn (HttpRequest $request) => $request->hasHeader('Authorization', 'Basic ZmFrZS11c2VyOmZha2UtcGFzcw==')
            && $request['to'] === '2290197000000'
        );
    }

    public function test_it_returns_a_failure_when_emc_reports_a_business_error(): void
    {
        $this->configureProvider();

        Http::fake([
            'https://api.e-mc.co/v3/sms' => Http::response([
                'data' => ['success' => false, 'message' => 'Solde insuffisant'],
            ], 200),
        ]);

        $response = (new EMcSmsProvider)->send('+22997000000', 'Bonjour');

        $this->assertFalse($response->success);
        $this->assertStringContainsString('Solde insuffisant', (string) $response->error);
    }

    public function test_it_returns_an_http_failure_without_exposing_credentials(): void
    {
        $this->configureProvider();

        Http::fake([
            'https://api.e-mc.co/v3/sms' => Http::response([
                'message' => 'Insufficient balance',
            ], 403),
        ]);

        $provider = new EMcSmsProvider;
        $response = $provider->send('+22997000000', 'Bonjour');

        $this->assertFalse($response->success);
        $this->assertSame(403, $provider->lastHttpStatus());
        $this->assertStringContainsString('e-MC HTTP 403', (string) $response->error);
        $this->assertStringContainsString('Insufficient balance', (string) $response->error);
        $this->assertStringNotContainsString('ZmFrZS11c2VyOmZha2UtcGFzcw==', (string) $response->error);
        $this->assertStringNotContainsString('secret', (string) $response->error);
    }

    public function test_it_rejects_missing_credentials_without_calling_the_api(): void
    {
        $this->configureProvider([
            'services.sms.emc.username' => null,
            'services.sms.emc.password' => null,
            'services.sms.emc.api_key' => null,
        ]);

        Http::fake();

        $response = (new EMcSmsProvider)->send('+22997000000', 'Bonjour');

        $this->assertFalse($response->success);
        $this->assertStringContainsString('Identifiants e-MC manquants', (string) $response->error);
        Http::assertNothingSent();
    }

    public function test_it_rejects_an_invalid_phone_number_without_calling_the_api(): void
    {
        $this->configureProvider();

        Http::fake();

        $response = (new EMcSmsProvider)->send('abc', 'Bonjour');

        $this->assertFalse($response->success);
        $this->assertStringContainsString('Numéro de téléphone invalide', (string) $response->error);
        Http::assertNothingSent();
    }

    public function test_it_normalizes_benin_numbers_to_the_e_mc_international_format(): void
    {
        $this->configureProvider();

        Http::fake([
            'https://api.e-mc.co/v3/sms' => Http::response([
                'data' => ['success' => true, 'messageId' => 'msg'],
            ], 200),
        ]);

        $inputs = ['97000000', '22997000000', '+22997000000', '0022997000000', '0197000000', '+2290197000000'];

        foreach ($inputs as $input) {
            (new EMcSmsProvider)->send($input, 'Bonjour');
        }

        $destinations = collect(Http::recorded())
            ->map(fn (array $pair) => $pair[0]['to'])
            ->all();

        $this->assertSame(array_fill(0, count($inputs), '2290197000000'), $destinations);
    }

    public function test_the_artisan_command_sends_a_single_sms_and_masks_the_number(): void
    {
        $this->configureProvider();

        Http::fake([
            'https://api.e-mc.co/v3/sms' => Http::response([
                'data' => ['success' => true, 'messageId' => 'cmd-123'],
            ], 200),
        ]);

        $this->artisan('corischool:test-emc-sms', [
            'to' => '+22997000000',
        ])
            ->expectsOutputToContain('Provider : emc')
            ->expectsOutputToContain('Numéro : +229******00')
            ->expectsOutputToContain('HTTP status : 200')
            ->expectsOutputToContain('Succès (message ID: cmd-123)')
            ->doesntExpectOutputToContain('+22997000000')
            ->assertSuccessful();

        Http::assertSentCount(1);
    }

    public function test_the_artisan_command_uses_the_configured_test_recipient(): void
    {
        $this->configureProvider([
            'services.sms.emc.test_to' => '+22997000000',
        ]);

        Http::fake([
            'https://api.e-mc.co/v3/sms' => Http::response([
                'data' => ['success' => true, 'messageId' => 'cmd-env-123'],
            ], 200),
        ]);

        $this->artisan('corischool:test-emc-sms')
            ->expectsOutputToContain('Numéro : +229******00')
            ->expectsOutputToContain('Succès (message ID: cmd-env-123)')
            ->assertSuccessful();

        Http::assertSent(fn (HttpRequest $request) => $request['to'] === '2290197000000');
    }
}
