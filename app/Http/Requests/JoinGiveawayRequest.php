<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class JoinGiveawayRequest extends FormRequest
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
        ];
    }
}
