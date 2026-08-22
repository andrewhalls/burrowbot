<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\GuildAdminFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuildAdmin extends Model
{
    /** @use HasFactory<GuildAdminFactory> */
    use HasFactory;

    public const SOURCE_DISCORD_SYNC = 'discord_sync';

    public const SOURCE_GRANTED = 'granted';

    protected $fillable = [
        'guild_id',
        'user_id',
        'discord_user_id',
        'role',
        'source',
        'sections',
    ];

    protected function casts(): array
    {
        return [
            'sections' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Guild, $this>
     */
    public function guild(): BelongsTo
    {
        return $this->belongsTo(Guild::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Whether this admin row grants access to the given dashboard section -
     * always true for a Discord-synced (full) admin, and true for a granted
     * (scoped) admin only when that section is in their grant.
     *
     * See openspec specs/guild-admin-permissions - design.md Decision 1.
     */
    public function hasSection(string $section): bool
    {
        return $this->source === self::SOURCE_DISCORD_SYNC
            || in_array($section, $this->sections ?? [], true);
    }

    public function isDiscordSynced(): bool
    {
        return $this->source === self::SOURCE_DISCORD_SYNC;
    }
}
