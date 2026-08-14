<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StandardGiveawayEntryRequest extends FormRequest
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
            'discord_role_ids' => ['sometimes', 'array'],
            'discord_role_ids.*' => ['string'],
            'is_boosting' => ['sometimes', 'boolean'],
        ];
    }
}
