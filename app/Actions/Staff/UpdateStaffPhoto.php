<?php

namespace App\Actions\Staff;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Photo d'un membre du personnel.
 *
 * Même convention que le logo de l'établissement (UpdateSchoolSettings) :
 * chemin relatif stocké sur le disque `public`, URL exposée séparément,
 * ancienne photo supprimée au moment du remplacement.
 */
class UpdateStaffPhoto
{
    public function handle(object $member, ?UploadedFile $photo, bool $remove): ?string
    {
        $old = $member->photo_path;

        if ($photo) {
            $new = $photo->store("schools/{$member->school_id}/staff", 'public');
            $member->forceFill(['photo_path' => $new])->save();

            if ($old && $old !== $new) {
                Storage::disk('public')->delete($old);
            }

            return $new;
        }

        if ($remove && $old) {
            $member->forceFill(['photo_path' => null])->save();
            Storage::disk('public')->delete($old);
        }

        return $member->photo_path;
    }
}
