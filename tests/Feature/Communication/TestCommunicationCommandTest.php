<?php

namespace Tests\Feature\Communication;

use App\Enums\CommunicationChannel;
use App\Models\School;
use App\Models\User;
use App\Services\Communication\CommunicationTransport;
use App\Services\Sms\SmsResponse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class TestCommunicationCommandTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.whatsapp.token' => 'fake-whatsapp-token',
            'services.whatsapp.phone_number_id' => 'fake-phone-number-id',
        ]);

        $this->school = School::factory()->create(['name' => 'École Test']);
        $this->user = User::factory()->admin()->forSchool($this->school)->create();
    }

    public function test_it_sends_to_a_single_recipient_on_the_requested_channel(): void
    {
        $this->mock(CommunicationTransport::class, function ($mock) {
            $mock->shouldReceive('send')
                ->once()
                ->with(
                    CommunicationChannel::Sms,
                    Mockery::type(School::class),
                    '+22990000001',
                    'Message de test'
                )
                ->andReturn(SmsResponse::success('sms_123'));
        });

        $this->artisan('corischool:test-communication', [
            'email' => $this->user->email,
            'to' => '+22990000001',
            '--channel' => 'sms',
            '--message' => 'Message de test',
        ])
            ->expectsOutputToContain('Succès via SMS')
            ->assertSuccessful();
    }

    public function test_fallback_uses_the_real_whatsapp_then_sms_order(): void
    {
        $this->mock(CommunicationTransport::class, function ($mock) {
            $mock->shouldReceive('send')
                ->once()
                ->with(CommunicationChannel::Whatsapp, Mockery::type(School::class), '+22990000001', Mockery::type('string'))
                ->andReturn(SmsResponse::failure('WhatsApp indisponible'));

            $mock->shouldReceive('send')
                ->once()
                ->with(CommunicationChannel::Sms, Mockery::type(School::class), '+22990000001', Mockery::type('string'))
                ->andReturn(SmsResponse::success('sms_123'));
        });

        $this->artisan('corischool:test-communication', [
            'email' => $this->user->email,
            'to' => '+22990000001',
            '--fallback' => true,
        ])
            ->expectsOutputToContain('Échec via WhatsApp')
            ->expectsOutputToContain('Succès via SMS')
            ->assertSuccessful();
    }

    public function test_it_rejects_an_email_channel_for_a_phone_number(): void
    {
        $this->artisan('corischool:test-communication', [
            'email' => $this->user->email,
            'to' => '+22990000001',
            '--channel' => 'email',
        ])
            ->expectsOutputToContain('Le canal email exige une adresse email valide.')
            ->assertFailed();
    }

    public function test_it_rejects_an_unknown_user(): void
    {
        $this->artisan('corischool:test-communication', [
            'email' => 'inconnu@example.com',
            'to' => '+22990000001',
        ])
            ->expectsOutputToContain('Utilisateur introuvable ou sans établissement.')
            ->assertFailed();
    }
}
