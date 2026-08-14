<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SyncGuildChannelsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'channels' => ['present', 'array'],
            'channels.*.discord_channel_id' => ['required', 'string'],
            'channels.*.name' => ['required', 'string', 'max:255'],
        ];
    }
}
