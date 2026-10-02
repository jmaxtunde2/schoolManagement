<?php

namespace App\Http\Requests\Notifications;

use App\Enums\NotificationStatus;
use Illuminate\Foundation\Http\FormRequest;

class ResendNotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin() && $this->route('notification')->status === NotificationStatus::Failed;
    }

    public function rules(): array
    {
        return [];
    }
}
