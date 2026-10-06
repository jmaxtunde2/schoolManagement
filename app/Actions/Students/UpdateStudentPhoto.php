<?php

namespace App\Actions\Students;

use App\Models\Student;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Photo d'un élève.
 *
 * Reprend la convention existante du logo d'établissement : le fichier vit sur
 * le disque `public` (`schools/{school_id}/students`), la base ne stocke que le
 * chemin, et l'ancienne photo est supprimée lors du remplacement.
 */
class UpdateStudentPhoto
{
    public function handle(Student $student, ?UploadedFile $photo, bool $remove): ?string
    {
        $old = $student->photo_path;

        if ($photo) {
            $new = $photo->store("schools/{$student->school_id}/students", 'public');
            $student->forceFill(['photo_path' => $new])->save();

            if ($old && $old !== $new) {
                Storage::disk('public')->delete($old);
            }

            return $new;
        }

        if ($remove && $old) {
            $student->forceFill(['photo_path' => null])->save();
            Storage::disk('public')->delete($old);
        }

        return $student->photo_path;
    }
}
