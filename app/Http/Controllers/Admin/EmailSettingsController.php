<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Mail\SchoolMailService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class EmailSettingsController extends Controller
{
    public function edit(Request $request): Response
    {
        $school = $request->user()->school;
        $settings = $school->mailSettings()->first();

        return Inertia::render('Settings/EmailNotifications', [
            'settings' => [
                'mailer' => $settings?->mailer ?? 'smtp',
                'host' => $settings?->host ?? '',
                'port' => (int) ($settings?->port ?? 587),
                'username' => $settings?->username ?? '',
                'password' => '',
                'encryption' => $settings?->encryption ?? 'tls',
                'from_address' => $settings?->from_address ?? '',
                'from_name' => $settings?->from_name ?? $school->name,
                'reply_to' => $settings?->reply_to ?? '',
                'is_active' => (bool) ($settings?->is_active ?? true),
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'mailer' => ['nullable', 'string', 'max:30'],
            'host' => ['required', 'string', 'max:255'],
            'port' => ['required', 'integer', 'min:1', 'max:65535'],
            'username' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string'],
            'encryption' => ['nullable', 'string', 'max:20'],
            'from_address' => ['required', 'email'],
            'from_name' => ['required', 'string', 'max:255'],
            'reply_to' => ['nullable', 'email'],
            'is_active' => ['boolean'],
        ]);

        $school = $request->user()->school;
        $settings = $school->mailSettings()->firstOrNew();

        $settings->fill([
            'mailer' => $data['mailer'] ?? 'smtp',
            'host' => $data['host'],
            'port' => $data['port'],
            'username' => $data['username'] ?? null,
            'password' => $request->filled('password') ? $data['password'] : ($settings->password ?? null),
            'encryption' => $data['encryption'] ?? null,
            'from_address' => $data['from_address'],
            'from_name' => $data['from_name'],
            'reply_to' => $data['reply_to'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        if ($settings->isDirty('password') && empty($settings->password)) {
            $settings->password = null;
        }

        $settings->save();

        return back()->with('success', 'Configuration SMTP enregistrée.');
    }

    public function sendTestEmail(Request $request): RedirectResponse
    {
        $school = $request->user()->school;

        try {
            app(SchoolMailService::class)->send(
                $school,
                $request->user()->email,
                $request->user()->name,
                'Test de configuration email — '.$school->name,
                'Ceci est un email de test envoyé depuis la plateforme scolaire.'
            );

            return back()->with('success', '✓ Email envoyé avec succès.');
        } catch (RuntimeException $exception) {
            return back()->with('error', '✗ Échec de l\'envoi : '.$exception->getMessage());
        }
    }
}
