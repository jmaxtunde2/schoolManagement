<?php

namespace App\Http\Requests\Demo;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation de la demande de démonstration publique.
 *
 * Le formulaire est volontairement court : seules les informations nécessaires
 * pour recontacter l'école et caler la démo sont exigées. Aucune donnée
 * administrative (type d'école, effectifs, besoin…) n'est demandée ici — elles
 * seront saisies par le Super Admin Cori au traitement de la demande.
 *
 * Aucun school_id n'est accepté : soumettre une demande ne crée ni école,
 * ni compte, ni licence.
 */
class StoreDemoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'school_name' => is_string($this->input('school_name')) ? trim($this->input('school_name')) : $this->input('school_name'),
            'address' => is_string($this->input('address')) ? trim($this->input('address')) : $this->input('address'),
            'phone' => is_string($this->input('phone')) ? trim($this->input('phone')) : $this->input('phone'),
            'email' => is_string($this->input('email')) ? strtolower(trim($this->input('email'))) : $this->input('email'),
            'preferred_demo_time' => $this->normalizeTime($this->input('preferred_demo_time')),
        ]);
    }

    public function rules(): array
    {
        return [
            'school_name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'regex:/^[0-9+\s().-]{6,30}$/'],
            'email' => ['required', 'email', 'max:255'],

            'preferred_demo_date' => ['required', 'date', 'after_or_equal:today'],
            'preferred_demo_time' => ['required', 'date_format:H:i'],
        ];
    }

    public function attributes(): array
    {
        return [
            'school_name' => "nom de l'établissement",
            'address' => 'adresse',
            'phone' => 'téléphone',
            'email' => 'adresse e-mail',
            'preferred_demo_date' => 'date souhaitée pour la démonstration',
            'preferred_demo_time' => 'heure souhaitée',
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex' => 'Le numéro de téléphone n’est pas valide.',
            'preferred_demo_date.after_or_equal' => 'La date de démonstration doit être aujourd’hui ou plus tard.',
            'preferred_demo_time.date_format' => 'L’heure doit être au format HH:MM.',
        ];
    }

    /** Accepte « 14:30 », « 14:30:00 » ou « 2:30 PM » et renvoie toujours H:i. */
    private function normalizeTime(mixed $value): mixed
    {
        if (! is_string($value) || trim($value) === '') {
            return $value;
        }

        $value = trim($value);

        if (preg_match('/^(\d{1,2}):(\d{2})(?::\d{2})?$/', $value, $matches)) {
            return sprintf('%02d:%s', (int) $matches[1], $matches[2]);
        }

        return $value;
    }
}
