<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Guild extends Model
{
    /** @use HasFactory<\Database\Factories\GuildFactory> */
    use HasFactory;

    protected $fillable = [
        'discord_guild_id',
        'name',
        'default_channel_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<GuildAdmin, $this>
     */
    public function guildAdmins(): HasMany
    {
        return $this->hasMany(GuildAdmin::class);
    }

    /**
     * @return HasMany<DiscordMember, $this>
     */
    public function discordMembers(): HasMany
    {
        return $this->hasMany(DiscordMember::class);
    }

    /**
     * @return HasMany<CollectionTheme, $this>
     */
    public function collectionThemes(): HasMany
    {
        return $this->hasMany(CollectionTheme::class);
    }

    /**
     * @return HasMany<Giveaway, $this>
     */
    public function giveaways(): HasMany
    {
        return $this->hasMany(Giveaway::class);
    }

    /**
     * @return HasMany<EventRoleSet, $this>
     */
    public function eventRoleSets(): HasMany
    {
        return $this->hasMany(EventRoleSet::class);
    }

    /**
     * @return HasMany<Event, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    /**
     * @return HasMany<StandardGiveaway, $this>
     */
    public function standardGiveaways(): HasMany
    {
        return $this->hasMany(StandardGiveaway::class);
    }
}
