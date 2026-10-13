<?php

namespace App\Http\Requests\Announcements;

use App\Models\Announcement;
use App\Models\ClassRoom;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAnnouncementRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user && ($user->isAdmin() || $user->isCenseur() || $user->isSecretary());
    }

    public function rules(): array
    {
        $schoolId = $this->user()->school_id;

        $levels = ClassRoom::withoutGlobalScopes()
            ->where('school_id', $schoolId)
            ->whereNotNull('level')
            ->where('level', '!=', '')
            ->distinct()
            ->pluck('level')
            ->all();

        return [
            'title' => ['required', 'string', 'max:180'],
            'body' => ['required', 'string', 'max:4000'],

            'channels' => ['required', 'array', 'min:1'],
            'channels.*' => [Rule::in(Announcement::CHANNELS)],

            'audience_type' => ['required', Rule::in(Announcement::AUDIENCES)],

            'class_ids' => [
                'required_if:audience_type,'.Announcement::AUDIENCE_CLASSES,
                'array',
            ],
            'class_ids.*' => [
                'integer',
                Rule::exists('classes', 'id')->where('school_id', $schoolId),
            ],

            'levels' => [
                'required_if:audience_type,'.Announcement::AUDIENCE_LEVELS,
                'array',
            ],
            'levels.*' => ['string', Rule::in($levels)],

            'guardian_ids' => [
                'required_if:audience_type,'.Announcement::AUDIENCE_GUARDIANS,
                'array',
            ],
            'guardian_ids.*' => [
                'integer',
                Rule::exists('parents', 'id')->where('school_id', $schoolId),
            ],

            'scheduled_at' => ['nullable', 'date', 'after:now'],

            'attachment' => [
                'nullable',
                'file',
                'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,webp',
                'max:5120',
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => 'objet',
            'body' => 'message',
            'channels' => 'canaux',
            'audience_type' => 'destinataires',
            'class_ids' => 'classes',
            'levels' => 'niveaux',
            'guardian_ids' => 'parents',
            'scheduled_at' => 'date d\'envoi',
            'attachment' => 'pièce jointe',
        ];
    }

    public function messages(): array
    {
        return [
            'channels.required' => 'Sélectionnez au moins un canal (WhatsApp ou Email).',
            'class_ids.required_if' => 'Sélectionnez au moins une classe.',
            'levels.required_if' => 'Sélectionnez au moins un niveau.',
            'guardian_ids.required_if' => 'Sélectionnez au moins un parent.',
        ];
    }
}
