<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A guild's postable Discord channel (text or announcement type only - see
 * design.md Decision 2), synced from the bot. See openspec
 * specs/discord-channels.
 */
class DiscordChannel extends Model
{
    /** @use HasFactory<\Database\Factories\DiscordChannelFactory> */
    use HasFactory;

    protected $fillable = [
        'guild_id',
        'discord_channel_id',
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
