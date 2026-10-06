<?php

namespace App\Http\Requests\Evaluations;

use App\Enums\EvaluationType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreEvaluationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()->role?->value, ['teacher', 'secretary'], true);
    }

    public function rules(): array
    {
        return ['class_id' => ['required', 'integer', Rule::exists('classes', 'id')->where('school_id', $this->user()->school_id)], 'subject_id' => ['required', 'integer', Rule::exists('subjects', 'id')->where('school_id', $this->user()->school_id)], 'teacher_id' => [Rule::requiredIf($this->user()->isSecretary()), 'nullable', 'integer', Rule::exists('teachers', 'id')->where('school_id', $this->user()->school_id)], 'title' => ['required', 'string', 'max:255'], 'type' => ['required', Rule::in(array_column(EvaluationType::cases(), 'value'))], 'evaluation_date' => ['required', 'date'], 'max_score' => ['required', 'numeric', 'min:1', 'max:1000'], 'coefficient' => ['required', 'numeric', 'min:0.5', 'max:10']];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $class = (int) $this->input('class_id');
            $subject = (int) $this->input('subject_id');
            if ($this->user()->isTeacher() && $class && $subject && ! $this->user()->teacher->isAssignedTo($class, $subject)) {
                $validator->errors()->add('subject_id', "Vous n'êtes pas habilité à évaluer cette matière dans cette classe.");
            }
        });
    }

    public function attributes(): array
    {
        return ['class_id' => 'classe', 'subject_id' => 'matière', 'teacher_id' => 'enseignant', 'evaluation_date' => "date de l'évaluation", 'max_score' => 'barème', 'coefficient' => 'coefficient'];
    }
}
