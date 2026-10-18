<?php

namespace App\Http\Requests\ExamBank;

use App\Enums\ExamDifficulty;
use App\Enums\ExamResourceStatus;
use App\Enums\ExamResourceType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExamResourceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isPlatformAdmin();
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:5000'],
            'platform_level_id' => ['nullable', 'integer', Rule::exists('platform_levels', 'id')],
            'platform_subject_id' => ['nullable', 'integer', Rule::exists('platform_subjects', 'id')],
            'type' => ['required', Rule::in(ExamResourceType::values())],
            'difficulty' => ['required', Rule::in(ExamDifficulty::values())],
            'duration_minutes' => ['nullable', 'integer', 'min:1', 'max:600'],
            'academic_year' => ['nullable', 'string', 'max:20'],
            'period' => ['nullable', 'string', 'max:30'],
            'content' => ['nullable', 'string'],
            'is_free' => ['boolean'],
            'status' => ['required', Rule::in(ExamResourceStatus::values())],
            'document_file' => ['nullable', 'file', 'mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,jpg,jpeg,png,webp', 'max:20480'],
            'answer_key_file' => ['nullable', 'file', 'mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,jpg,jpeg,png,webp', 'max:20480'],
        ];
    }
}
