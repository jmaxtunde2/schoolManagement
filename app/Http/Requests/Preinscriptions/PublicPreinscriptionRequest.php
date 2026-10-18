<?php

namespace App\Http\Requests\Preinscriptions;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation du formulaire public de préinscription (phase 7).
 *
 * Aucun champ de périmètre (school_id) n'est accepté depuis le client :
 * l'établissement provient toujours du slug de la route.
 */
class PublicPreinscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:120'],
            'last_name' => ['required', 'string', 'max:120'],
            'birth_date' => ['nullable', 'date', 'before_or_equal:today'],
            'gender' => ['nullable', Rule::in(['M', 'F'])],
            'requested_level' => ['nullable', 'string', 'max:40'],

            'parent_name' => ['required', 'string', 'max:160'],
            'parent_phone' => ['required', 'string', 'max:40'],
            'parent_email' => ['nullable', 'email', 'max:255'],
            'parent_relationship' => ['nullable', Rule::in(['Père', 'Mère', 'Tuteur', 'Autre'])],

            'notes' => ['nullable', 'string', 'max:2000'],

            'documents' => ['nullable', 'array', 'max:5'],
            'documents.*' => [
                'file',
                'mimes:pdf,jpg,jpeg,png,webp',
                'max:5120',
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'first_name' => 'prénom de l’élève',
            'last_name' => 'nom de l’élève',
            'birth_date' => 'date de naissance',
            'gender' => 'sexe',
            'requested_level' => 'niveau souhaité',
            'parent_name' => 'nom du parent',
            'parent_phone' => 'téléphone',
            'parent_email' => 'email',
            'parent_relationship' => 'lien de parenté',
            'documents' => 'pièces jointes',
            'documents.*' => 'pièce jointe',
        ];
    }

    public function messages(): array
    {
        return [
            'documents.max' => 'Vous ne pouvez joindre que 5 documents.',
            'documents.*.mimes' => 'Les documents doivent être des PDF, JPG, PNG ou WebP.',
            'documents.*.max' => 'Chaque document ne doit pas dépasser 5 Mo.',
        ];
    }
}
