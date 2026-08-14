<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EventSignupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'discord_user_id' => ['required', 'string'],
            'discord_username' => ['required', 'string', 'max:255'],
            'discord_display_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'event_role_id' => ['nullable', 'integer', 'exists:event_roles,id'],
        ];
    }
}
