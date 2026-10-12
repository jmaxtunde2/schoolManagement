<?php

namespace App\Http\Requests\Reference;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        $id = $this->route('room')?->id;

        return [
            'name' => ['required', 'string', 'max:60', Rule::unique('rooms')->where('school_id', $this->user()->school_id)->ignore($id)],
            'building' => ['nullable', 'string', 'max:60'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:500'],
        ];
    }
}