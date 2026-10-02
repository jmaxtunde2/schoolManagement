<?php

namespace App\Http\Requests\Attendance;

use App\Enums\AttendanceStatus;
use App\Models\AttendanceRecord;
use App\Models\ClassRoom;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $classRoom = ClassRoom::withoutGlobalScopes()
            ->where('school_id', $this->user()->school_id)
            ->find($this->input('class_room_id'));

        return $classRoom
            && Gate::allows('create', [AttendanceRecord::class, $classRoom]);
    }

    public function rules(): array
    {
        $classRoomId = $this->integer('class_room_id');
        $schoolId = $this->user()->school_id;

        return [
            'class_room_id' => [
                'required',
                'integer',
                Rule::exists('classes', 'id')->where('school_id', $schoolId),
            ],
            'attendance_date' => ['required', 'date', 'before_or_equal:today'],
            'academic_period_id' => [
                'nullable',
                'integer',
                Rule::exists('academic_periods', 'id')->where('school_id', $schoolId),
            ],
            'records' => ['required', 'array', 'min:1', 'max:500'],
            'records.*.student_id' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('students', 'id')
                    ->where('school_id', $schoolId)
                    ->where('class_id', $classRoomId)
                    ->where('is_active', true),
            ],
            'records.*.status' => ['required', Rule::enum(AttendanceStatus::class)],
            'records.*.delay_minutes' => ['nullable', 'integer', 'min:0', 'max:1440'],
            'records.*.reason' => ['nullable', 'string', 'max:500'],
            'records.*.note' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $periodId = $this->input('academic_period_id');
            if ($periodId) {
                $period = \App\Models\AcademicPeriod::withoutGlobalScopes()
                    ->where('school_id', $this->user()->school_id)
                    ->find($periodId);

                if ($period && (
                    $period->is_closed
                    || $this->date('attendance_date') < $period->starts_at
                    || $this->date('attendance_date') > $period->ends_at
                )) {
                    $validator->errors()->add(
                        'academic_period_id',
                        'La période est fermée ou ne correspond pas à la date sélectionnée.'
                    );
                }
            }

            foreach ($this->input('records', []) as $index => $record) {
                if (($record['status'] ?? null) === AttendanceStatus::Late->value
                    && blank($record['delay_minutes'] ?? null)) {
                    $validator->errors()->add(
                        "records.{$index}.delay_minutes",
                        'Indiquez le nombre de minutes de retard.'
                    );
                }
            }
        });
    }
}