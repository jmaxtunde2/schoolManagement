<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\School;
use App\Models\SchoolDomain;
use App\Models\SchoolSetting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Deux écoles aux identités visuelles différentes pour vérifier le thème dynamique
 * et l'isolation des données. Mot de passe de tous les comptes : "password".
 */
class SchoolSeeder extends Seeder
{
    public function run(): void
    {
        $platform = User::firstOrNew(['email' => 'platform@coriyase.test']);
        $platform->forceFill(['name' => 'Coriyase Platform Admin', 'school_id' => null, 'role' => Role::PlatformAdmin, 'is_active' => true, 'password' => Hash::make('password'), 'email_verified_at' => now()])->save();

        $schools = [
            [
                'name' => "Lycée d'Excellence de Cotonou",
                'slug' => 'lycee-excellence',
                'settings' => [
                    'school_name' => "Lycée d'Excellence de Cotonou",
                    'short_name' => 'LEC',
                    'slogan' => 'Savoir, discipline, réussite',
                    'description' => "Établissement d'enseignement secondaire général.",
                    'school_type' => 'lycee',
                    'founded_year' => 1998,
                    'address' => 'Rue 123, Quartier Haie Vive',
                    'city' => 'Cotonou',
                    'country' => 'Bénin',
                    'phone' => '+229 01 97 00 00 01',
                    'email' => 'contact@lycee-excellence.test',
                    'contact_name' => 'Mme Adjovi',
                    'primary_color' => '#1E40AF',
                    'secondary_color' => '#0F766E',
                    'accent_color' => '#D97706',
                ],
                'users' => [
                    ['Administrateur LEC', 'admin@lycee-excellence.test', Role::Admin],
                    ['Marie Houngbo', 'prof@lycee-excellence.test', Role::Teacher],
                    ['Censeur LEC', 'censeur@lycee-excellence.test', Role::Censeur],
                    ['Secrétaire LEC', 'secretary@lycee-excellence.test', Role::Secretary],
                ],
            ],
            [
                'name' => 'Collège Les Palmiers',
                'slug' => 'college-palmiers',
                'settings' => [
                    'school_name' => 'Collège Les Palmiers',
                    'short_name' => 'CLP',
                    'slogan' => 'Grandir ensemble',
                    'school_type' => 'college',
                    'city' => 'Porto-Novo',
                    'country' => 'Bénin',
                    'phone' => '+229 01 97 00 00 02',
                    'email' => 'contact@palmiers.test',
                    'primary_color' => '#6D28D9',
                    'secondary_color' => '#2563EB',
                    'accent_color' => '#CA8A04',
                ],
                'users' => [
                    ['Administrateur CLP', 'admin@palmiers.test', Role::Admin],
                    ['Paul Agbodjan', 'prof@palmiers.test', Role::Teacher],
                ],
            ],
        ];

        foreach ($schools as $data) {
            $school = School::updateOrCreate(['slug' => $data['slug']], ['name' => $data['name'], 'is_active' => true]);

            SchoolSetting::withoutGlobalScopes()->updateOrCreate(['school_id' => $school->id], $data['settings']);

            // Domaines pour le développement local (utilisez Laravel Herd/Valet ou ajoutez à /etc/hosts : 127.0.0.1 lycee-excellence.test)
            SchoolDomain::updateOrCreate(
                ['school_id' => $school->id, 'domain' => $data['slug'] . '.test'],
                ['is_primary' => true, 'verified' => true]
            );

            foreach ($data['users'] as [$name, $email, $role]) {
                $user = User::firstOrNew(['email' => $email]);
                $user->forceFill([
                    'name' => $name,
                    'school_id' => $school->id,
                    'role' => $role,
                    'is_active' => true,
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ])->save();
            }
        }
    }
}
