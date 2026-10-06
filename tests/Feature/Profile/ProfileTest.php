<?php

namespace Tests\Feature\Profile;

use App\Enums\Role;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create();
        $this->user = User::factory()->teacher()->forSchool($this->school)->create([
            'name' => 'Marie Houngbo',
            'email' => 'marie@example.test',
            'password' => 'ancien-mot-de-passe',
        ]);

        // La factory pose `two_factor_confirmed_at` mais pas la date d'activation.
        $this->user->forceFill(['two_factor_enabled_at' => now()->subDays(30)])->save();
    }

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $this->get(route('profile.edit'))->assertRedirect(route('login'));
    }

    public function test_profile_page_shows_the_current_user_account_details(): void
    {
        $this->actingAs($this->user)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Profile/Edit')
                ->where('profile.name', 'Marie Houngbo')
                ->where('profile.email', 'marie@example.test')
                ->where('profile.role', 'teacher')
                ->where('profile.role_label', 'Enseignant')
                ->where('profile.school_name', $this->school->name)
                ->where('profile.is_email_verified', true)
                ->where('profile.two_factor_enabled', true)
                ->where('profile.two_factor_enabled_at', $this->user->two_factor_enabled_at->isoFormat('LL'))
            );
    }

    public function test_every_role_can_reach_their_own_profile(): void
    {
        foreach ([User::factory()->admin(), User::factory()->teacher()] as $factory) {
            $user = $factory->forSchool($this->school)->create();
            $this->actingAs($user)->get(route('profile.edit'))->assertOk();
        }

        // L'administrateur Coriyase n'appartient à aucun établissement.
        $platform = User::factory()->create(['role' => 'platform_admin', 'school_id' => null]);
        $this->actingAs($platform)->get(route('profile.edit'))->assertOk();
    }

    public function test_a_user_updates_their_name_without_proving_their_password(): void
    {
        $this->actingAs($this->user)
            ->put(route('profile.update'), ['name' => 'Marie A. Houngbo', 'email' => 'marie@example.test'])
            ->assertSessionHasNoErrors();

        $this->user->refresh();

        $this->assertSame('Marie A. Houngbo', $this->user->name);
        $this->assertSame('marie@example.test', $this->user->email);
    }

    public function test_changing_the_email_requires_the_current_password(): void
    {
        $this->actingAs($this->user)
            ->put(route('profile.update'), ['name' => 'Marie Houngbo', 'email' => 'nouveau@example.test'])
            ->assertSessionHasErrors('current_password');

        $this->assertSame('marie@example.test', $this->user->refresh()->email);
    }

    public function test_changing_the_email_requires_the_correct_current_password(): void
    {
        $this->actingAs($this->user)
            ->put(route('profile.update'), [
                'name' => 'Marie Houngbo',
                'email' => 'nouveau@example.test',
                'current_password' => 'mauvais-mot-de-passe',
            ])
            ->assertSessionHasErrors('current_password');

        $this->assertSame('marie@example.test', $this->user->refresh()->email);
    }

    public function test_a_user_changes_their_email_with_the_correct_password(): void
    {
        $this->actingAs($this->user)
            ->put(route('profile.update'), [
                'name' => 'Marie Houngbo',
                'email' => '  Nouveau@Example.TEST  ',
                'current_password' => 'ancien-mot-de-passe',
            ])
            ->assertSessionHasNoErrors();

        $this->user->refresh();

        // L'adresse est normalisée avant validation.
        $this->assertSame('nouveau@example.test', $this->user->email);

        // L'état de vérification est préservé, l'identité ayant été prouvée.
        $this->assertNotNull($this->user->email_verified_at);
    }

    public function test_the_email_must_stay_unique(): void
    {
        $other = User::factory()->forSchool($this->school)->create(['email' => 'occupe@example.test']);

        $this->actingAs($this->user)
            ->put(route('profile.update'), [
                'name' => 'Marie Houngbo',
                'email' => 'occupe@example.test',
                'current_password' => 'ancien-mot-de-passe',
            ])
            ->assertSessionHasErrors('email');

        $this->assertSame('marie@example.test', $this->user->refresh()->email);
        $this->assertNotSame('marie@example.test', $other->email);
    }

    public function test_the_email_is_trimmed_before_the_uniqueness_check(): void
    {
        User::factory()->forSchool($this->school)->create(['email' => 'occupe@example.test']);

        $this->actingAs($this->user)
            ->put(route('profile.update'), [
                'name' => 'Marie Houngbo',
                'email' => ' OCCUPE@EXAMPLE.TEST ',
                'current_password' => 'ancien-mot-de-passe',
            ])
            ->assertSessionHasErrors('email');
    }

    public function test_name_and_email_are_required(): void
    {
        $this->actingAs($this->user)
            ->put(route('profile.update'), ['name' => '', 'email' => ''])
            ->assertSessionHasErrors(['name', 'email']);
    }

    public function test_a_user_changes_their_password(): void
    {
        $this->markSensitiveTwoFactorVerified('change_password');
        $this->actingAs($this->user)
            ->put(route('profile.password.update'), [
                'current_password' => 'ancien-mot-de-passe',
                'password' => 'nouveau-mot-de-passe-9',
                'password_confirmation' => 'nouveau-mot-de-passe-9',
            ])
            ->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('nouveau-mot-de-passe-9', $this->user->refresh()->password));
    }

    public function test_changing_a_password_requires_the_correct_current_password(): void
    {
        $this->markSensitiveTwoFactorVerified('change_password');
        $this->actingAs($this->user)
            ->put(route('profile.password.update'), [
                'current_password' => 'mauvais-mot-de-passe',
                'password' => 'nouveau-mot-de-passe-9',
                'password_confirmation' => 'nouveau-mot-de-passe-9',
            ])
            ->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('ancien-mot-de-passe', $this->user->refresh()->password));
    }

    public function test_the_new_password_must_be_confirmed(): void
    {
        $this->markSensitiveTwoFactorVerified('change_password');
        $this->actingAs($this->user)
            ->put(route('profile.password.update'), [
                'current_password' => 'ancien-mot-de-passe',
                'password' => 'nouveau-mot-de-passe-9',
                'password_confirmation' => 'autre-mot-de-passe-9',
            ])
            ->assertSessionHasErrors('password');
    }

    public function test_the_new_password_must_be_different_from_the_current_one(): void
    {
        $this->markSensitiveTwoFactorVerified('change_password');
        $this->actingAs($this->user)
            ->put(route('profile.password.update'), [
                'current_password' => 'ancien-mot-de-passe',
                'password' => 'ancien-mot-de-passe',
                'password_confirmation' => 'ancien-mot-de-passe',
            ])
            ->assertSessionHasErrors('password');
    }

    public function test_the_new_password_needs_letters_numbers_and_eight_characters(): void
    {
        $weak = [
            ['court1', 'Trop court'],
            ['uniquementdeslettres', 'Aucune lettre'],
            ['12345678901', 'Aucun chiffre'],
        ];

        $this->markSensitiveTwoFactorVerified('change_password');

        foreach ($weak as [$password, $label]) {
            $this->actingAs($this->user)
                ->put(route('profile.password.update'), [
                    'current_password' => 'ancien-mot-de-passe',
                    'password' => $password,
                    'password_confirmation' => $password,
                ])
                ->assertSessionHasErrors('password');
        }

        $this->assertTrue(Hash::check('ancien-mot-de-passe', $this->user->refresh()->password));
    }

    public function test_changing_a_password_disconnects_other_sessions_but_keeps_the_current_one(): void
    {
        /*
         * Le comportement testé concerne le pilote de session `database` :
         * `ProfileController` préserve la ligne de session de l'appareil courant et
         * supprime les autres. Les tests tournent par défaut avec le pilote `array`,
         * où aucune ligne de `sessions` n'est utilisée : on bascule donc sur
         * `database` pour que la session réellement employée soit une ligne de la table.
         */
        config(['session.driver' => 'database']);

        $otherUserId = User::factory()->forSchool($this->school)->create()->id;

        DB::table('sessions')->insert([
            ['id' => 'session-autre-appareil-1', 'user_id' => $this->user->id, 'ip_address' => '127.0.0.1', 'user_agent' => 'mobile', 'payload' => '', 'last_activity' => now()->timestamp],
            ['id' => 'session-autre-appareil-2', 'user_id' => $this->user->id, 'ip_address' => '127.0.0.1', 'user_agent' => 'tablet', 'payload' => '', 'last_activity' => now()->timestamp],
            ['id' => 'session-dun-autre-utilisateur', 'user_id' => $otherUserId, 'ip_address' => '127.0.0.1', 'user_agent' => 'x', 'payload' => '', 'last_activity' => now()->timestamp],
        ]);

        $this->actingAs($this->user);

        /*
         * Une requête inoffensive crée la ligne de session de l'appareil courant.
         * Elle est redirigée vers le challenge 2FA (cette session n'est pas encore
         * vérifiée), ce qui n'a aucune incidence : la ligne est bien écrite.
         * `withSession` ne convient pas ensuite : la session est relue depuis la base
         * par le middleware `StartSession`, on écrit donc le payload directement.
         */
        $this->get(route('profile.edit'));

        $currentSessionId = DB::table('sessions')
            ->whereNotIn('id', ['session-autre-appareil-1', 'session-autre-appareil-2', 'session-dun-autre-utilisateur'])
            ->value('id');

        $this->assertNotNull($currentSessionId, 'La requête courante doit avoir créé une ligne de session.');

        // Vérification 2FA à la connexion et revalidation de l'action sensible.
        DB::table('sessions')->where('id', $currentSessionId)->update([
            'payload' => base64_encode(serialize([
                '_token' => Str::random(40),
                'two_factor_verified_at' => now()->timestamp,
                'two_factor_sensitive' => ['change_password' => now()->timestamp],
            ])),
        ]);

        // Le driver de session est mémoïsé : on le vide pour que la requête
        // suivante relise bien le payload ci-dessus.
        $this->app['session']->forgetDrivers();

        $this->withCookie(config('session.cookie'), $currentSessionId)
            ->put(route('profile.password.update'), [
                'current_password' => 'ancien-mot-de-passe',
                'password' => 'nouveau-mot-de-passe-9',
                'password_confirmation' => 'nouveau-mot-de-passe-9',
            ])
            ->assertSessionHasNoErrors();

        $remaining = DB::table('sessions')->pluck('id')->all();

        $this->assertContains($currentSessionId, $remaining);
        $this->assertNotContains('session-autre-appareil-1', $remaining);
        $this->assertNotContains('session-autre-appareil-2', $remaining);

        // Les sessions d'un autre utilisateur ne sont pas touchées.
        $this->assertContains('session-dun-autre-utilisateur', $remaining);
    }

    public function test_changing_a_password_requires_a_fresh_two_factor_verification(): void
    {
        // Session vérifiée, mais sans revalidation sensible : c'est l'état
        // normal d'un utilisateur qui n'est pas en train de changer son mot de passe.
        // Sans en-tête Referer, le middleware renvoie vers la page d'accueil du rôle.
        $this->actingAs($this->user)
            ->put(route('profile.password.update'), [
                'current_password' => 'ancien-mot-de-passe',
                'password' => 'nouveau-mot-de-passe-9',
                'password_confirmation' => 'nouveau-mot-de-passe-9',
            ])
            ->assertRedirect(route('two-factor.challenge', [
                'purpose' => 'change_password',
                'return' => route(Role::Teacher->homeRoute(), [], false),
            ]));

        $this->assertTrue(Hash::check('ancien-mot-de-passe', $this->user->refresh()->password));
    }

    public function test_updating_the_profile_does_not_require_a_fresh_two_factor_verification(): void
    {
        $this->actingAs($this->user)
            ->put(route('profile.update'), ['name' => 'Marie A. H.', 'email' => 'marie@example.test'])
            ->assertSessionHasNoErrors();

        $this->assertSame('Marie A. H.', $this->user->refresh()->name);
    }
}
