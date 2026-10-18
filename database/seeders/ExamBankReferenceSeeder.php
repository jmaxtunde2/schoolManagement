<?php

namespace Database\Seeders;

use App\Models\PlatformLevel;
use App\Models\PlatformSubject;
use Illuminate\Database\Seeder;

/**
 * Référentiels de la banque d'épreuves : niveaux et matières gérés au
 * niveau plateforme. Les administrateurs peuvent les compléter depuis
 * l'interface (Platform → Banque d'épreuves → Référentiels).
 */
class ExamBankReferenceSeeder extends Seeder
{
    public function run(): void
    {
        $levels = [
            ['name' => '6e', 'position' => 1],
            ['name' => '5e', 'position' => 2],
            ['name' => '4e', 'position' => 3],
            ['name' => '3e', 'position' => 4],
            ['name' => '2nde', 'position' => 5],
            ['name' => '1ère', 'position' => 6],
            ['name' => 'Terminale', 'position' => 7],
        ];

        foreach ($levels as $level) {
            PlatformLevel::firstOrCreate(['name' => $level['name']], $level);
        }

        $subjects = [
            ['name' => 'Mathématiques', 'code' => 'MATH', 'position' => 1],
            ['name' => 'Français', 'code' => 'FR', 'position' => 2],
            ['name' => 'Anglais', 'code' => 'ANG', 'position' => 3],
            ['name' => 'SVT', 'code' => 'SVT', 'position' => 4],
            ['name' => 'Physique-Chimie', 'code' => 'PC', 'position' => 5],
            ['name' => 'Histoire-Géographie', 'code' => 'HG', 'position' => 6],
            ['name' => 'Philosophie', 'code' => 'PHILO', 'position' => 7],
            ['name' => 'Informatique', 'code' => 'INFO', 'position' => 8],
        ];

        foreach ($subjects as $subject) {
            PlatformSubject::firstOrCreate(['name' => $subject['name']], $subject);
        }
    }
}
