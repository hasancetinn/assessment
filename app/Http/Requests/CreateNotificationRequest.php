<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateNotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'recipient' => ['required', 'string', 'max:255'],
            'channel' => ['required', 'in:sms,email,push'],
            'content' => ['required', 'string'],
            'priority' => ['sometimes', 'in:high,normal,low'],
            'idempotency_key' => ['sometimes', 'string', 'max:255'],
            'scheduled_at' => ['sometimes', 'date', 'after:now'],
        ];
    }
}
