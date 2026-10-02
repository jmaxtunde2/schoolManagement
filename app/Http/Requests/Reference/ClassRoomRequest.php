<?php

namespace App\Http\Requests\Reference;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClassRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin()
            || ($this->user()->isCenseur() && $this->isMethod('post'));
    }

    public function rules(): array
    {
        $id = $this->route('class')?->id;

        return [
            'name' => ['required', 'string', 'max:60', Rule::unique('classes')->where('school_id', $this->user()->school_id)->ignore($id)],
            'level' => ['nullable', 'string', 'max:60'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:500'],
        ];
    }
}
