<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DiscordMember extends Model
{
    /** @use HasFactory<\Database\Factories\DiscordMemberFactory> */
    use HasFactory;

    protected $fillable = [
        'guild_id',
        'discord_user_id',
        'username',
        'display_name',
        'avatar_url',
    ];

    /**
     * The name to show in the dashboard: their Discord display name
     * (nickname-aware) when known, else their raw username.
     *
     * @return Attribute<string, never>
     */
    protected function displayNameOrUsername(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->display_name ?: $this->username,
        );
    }

    /**
     * @return BelongsTo<Guild, $this>
     */
    public function guild(): BelongsTo
    {
        return $this->belongsTo(Guild::class);
    }

    /**
     * @return HasMany<GiveawayEntry, $this>
     */
    public function giveawayEntries(): HasMany
    {
        return $this->hasMany(GiveawayEntry::class);
    }

    /**
     * @return HasMany<EventAttendance, $this>
     */
    public function eventAttendances(): HasMany
    {
        return $this->hasMany(EventAttendance::class);
    }

    /**
     * @return HasMany<EventRoleSignup, $this>
     */
    public function eventRoleSignups(): HasMany
    {
        return $this->hasMany(EventRoleSignup::class);
    }

    /**
     * Matches members whose username or Discord ID contains $term,
     * case-insensitively. Callers must additionally scope by guild_id -
     * this scope alone does not enforce guild isolation.
     *
     * @param  Builder<DiscordMember>  $query
     * @return Builder<DiscordMember>
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where(function (Builder $query) use ($term) {
            $query->whereLike('username', "%{$term}%")
                ->orWhereLike('discord_user_id', "%{$term}%");
        });
    }
}
