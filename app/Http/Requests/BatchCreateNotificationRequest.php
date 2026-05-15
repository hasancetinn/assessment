<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BatchCreateNotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'notifications' => ['required', 'array', 'min:1', 'max:1000'],
            'notifications.*.recipient' => ['required', 'string', 'max:255'],
            'notifications.*.channel' => ['required', 'in:sms,email,push'],
            'notifications.*.content' => ['required', 'string'],
            'notifications.*.priority' => ['sometimes', 'in:high,normal,low'],
            'notifications.*.idempotency_key' => ['sometimes', 'string', 'max:255'],
            'notifications.*.scheduled_at' => ['sometimes', 'date', 'after:now'],
        ];
    }
}
