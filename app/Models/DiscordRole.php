<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A guild's Discord role (excluding @everyone and Discord-managed roles -
 * see design.md Decision 2), synced from the bot. See openspec
 * specs/discord-roles.
 */
class DiscordRole extends Model
{
    /** @use HasFactory<\Database\Factories\DiscordRoleFactory> */
    use HasFactory;

    protected $fillable = [
        'guild_id',
        'discord_role_id',
        'name',
    ];

    /**
     * @return BelongsTo<Guild, $this>
     */
    public function guild(): BelongsTo
    {
        return $this->belongsTo(Guild::class);
    }
}
