<?php

namespace Tests\Feature\Notifications;

use App\Models\School;
use App\Models\SchoolMailSetting;
use App\Models\User;
use App\Models\UserNotification;
use App\Jobs\SendUserNotificationEmailJob;
use App\Services\Mail\SchoolMailService;
use App\Services\UserNotifications\UserNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class UserNotificationMailTest extends TestCase
{
    use RefreshDatabase;

    public function test_school_mail_service_uses_school_specific_configuration(): void
    {
        Mail::fake();

        $school = School::factory()->create(['name' => 'École Alpha']);

        SchoolMailSetting::create([
            'school_id' => $school->id,
            'mailer' => 'smtp',
            'host' => 'smtp.example.com',
            'port' => 587,
            'username' => 'alpha-user',
            'password' => 'secret-password',
            'encryption' => 'tls',
            'from_address' => 'noreply@alpha.example',
            'from_name' => 'École Alpha',
            'reply_to' => 'contact@alpha.example',
            'is_active' => true,
        ]);

        app(SchoolMailService::class)->send(
            $school,
            'teacher@alpha.example',
            'Prof Alpha',
            'Test de configuration',
            'Bonjour depuis l\'école Alpha.'
        );

        Mail::assertSent(function (\Illuminate\Mail\Mailable $mail) {
            return $mail->hasTo('teacher@alpha.example', 'Prof Alpha')
                && $mail->hasFrom('noreply@alpha.example', 'École Alpha')
                && $mail->replyTo[0]->address === 'contact@alpha.example';
        });
    }

    public function test_user_notification_service_creates_internal_notification(): void
    {
        Queue::fake();

        $school = School::factory()->create();
        $user = User::factory()->teacher()->forSchool($school)->create([
            'email' => 'teacher@example.com',
        ]);

        $notification = app(UserNotificationService::class)->send(
            $user,
            'evaluation.created',
            'Nouvelle évaluation',
            'Une nouvelle évaluation a été créée.',
            ['evaluation_id' => 42],
            false
        );

        $this->assertInstanceOf(UserNotification::class, $notification);
        $this->assertDatabaseHas('user_notifications', [
            'school_id' => $school->id,
            'user_id' => $user->id,
            'type' => 'evaluation.created',
        ]);

        Queue::assertNotPushed(SendUserNotificationEmailJob::class);
    }
}
