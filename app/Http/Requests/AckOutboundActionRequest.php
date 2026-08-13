<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AckOutboundActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'discord_message_id' => ['sometimes', 'nullable', 'string'],
            'discord_thread_id' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
