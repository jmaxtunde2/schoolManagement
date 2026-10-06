<?php

namespace App\Http\Requests\Timetable;

use App\Models\Timetable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Validation d'un créneau d'emploi du temps.
 *
 * Toutes les clés étrangères sont validées avec `Rule::exists(...)->where('school_id', ...)`
 * sur l'école de l'utilisateur connecté : un ID d'une autre école soumis dans le
 * formulaire est donc rejeté avant écriture. `school_id` n'est jamais une entrée
 * du formulaire, il est posé par le modèle.
 *
 * Les collisions (classe, enseignant, salle) sont vérifiées côté serveur : le
 * filtrage frontend ne constitue jamais une barrière.
 */
class TimetableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    protected function prepareForValidation(): void
    {
        foreach (['starts_at', 'ends_at'] as $key) {
            if (is_string($this->input($key))) {
                // Les sélecteurs HTML envoient HH:MM ; on tolère HH:MM:SS.
                $this->merge([$key => substr(trim($this->input($key)), 0, 5)]);
            }
        }
    }

    public function rules(): array
    {
        $schoolId = $this->user()->school_id;
        $slot = $this->route('timetable');
        $slotId = $slot?->id;

        return [
            'academic_year_id' => [
                'required',
                'integer',
                Rule::exists('academic_years', 'id')->where('school_id', $schoolId),
            ],

            'class_id' => [
                'required',
                'integer',
                Rule::exists('classes', 'id')->where('school_id', $schoolId),
            ],

            'subject_id' => [
                'required',
                'integer',
                Rule::exists('subjects', 'id')->where('school_id', $schoolId),
            ],

            'teacher_id' => [
                'nullable',
                'integer',
                Rule::exists('teachers', 'id')->where('school_id', $schoolId),
            ],

            'day_of_week' => ['required', 'integer', Rule::in(array_keys(Timetable::days()))],

            'starts_at' => ['required', 'string', 'regex:/^([01]\d|2[0-3]):[0-5]\d$/'],
            'ends_at' => ['required', 'string', 'regex:/^([01]\d|2[0-3]):[0-5]\d$/'],

            'room' => ['nullable', 'string', 'max:60'],
        ];
    }

    public function attributes(): array
    {
        return [
            'academic_year_id' => 'année scolaire',
            'class_id' => 'classe',
            'subject_id' => 'matière',
            'teacher_id' => 'enseignant',
            'day_of_week' => 'jour',
            'starts_at' => 'heure de début',
            'ends_at' => 'heure de fin',
            'room' => 'salle',
        ];
    }

    public function messages(): array
    {
        return [
            'starts_at.regex' => "L'heure de début doit être au format HH:MM.",
            'ends_at.regex' => "L'heure de fin doit être au format HH:MM.",
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $data = $this->validationData();

            if (! isset($data['starts_at'], $data['ends_at'])) {
                return;
            }

            // L'heure de fin doit être strictement postérieure à l'heure de début.
            if ($data['ends_at'] <= $data['starts_at']) {
                $validator->errors()->add(
                    'ends_at',
                    "L'heure de fin doit être postérieure à l'heure de début."
                );

                return;
            }

            $this->assertNoOverlap($validator, $data);
        });
    }

    /**
     * Un créneau ne peut pas chevaucher un créneau existant :
     * - de la même classe (la classe ne peut pas être à deux endroits) ;
     * - du même enseignant (l'enseignant ne peut pas dispenser deux cours) ;
     * - de la même salle, si une salle est renseignée.
     *
     * La comparaison se fait sur le même jour, même année scolaire et même école,
     * en excluant le créneau en cours d'édition.
     */
    private function assertNoOverlap(Validator $validator, array $data): void
    {
        $slotId = $this->route('timetable')?->id;

        $query = Timetable::query()
            ->where('school_id', $this->user()->school_id)
            ->where('academic_year_id', $data['academic_year_id'])
            ->where('day_of_week', $data['day_of_week'])
            ->when($slotId, fn ($q) => $q->where('id', '!=', $slotId))
            // Chevauchement : début < fin_du_créneau_existant ET fin > début_du_créneau_existant.
            ->where('starts_at', '<', $data['ends_at'])
            ->where('ends_at', '>', $data['starts_at']);

        $conflicts = (clone $query)->where('class_id', $data['class_id'])->exists();

        if ($conflicts) {
            $this->addOverlapError($validator, 'class_id', 'La classe a déjà un cours sur ce créneau.');
        }

        if (filled($data['teacher_id'] ?? null) && (clone $query)->where('teacher_id', $data['teacher_id'])->exists()) {
            $this->addOverlapError($validator, 'teacher_id', 'Cet enseignant a déjà un cours sur ce créneau.');
        }

        if (filled($data['room'] ?? null) && (clone $query)->where('room', $data['room'])->exists()) {
            $this->addOverlapError($validator, 'room', 'Cette salle est déjà occupée sur ce créneau.');
        }
    }

    private function addOverlapError(Validator $validator, string $key, string $message): void
    {
        $label = $this->attributes()[$key] ?? $key;

        $validator->errors()->add($key, "{$label} : {$message}");
    }
}
