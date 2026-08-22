<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'discord_user_id', 'avatar_url'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * @return HasMany<GuildAdmin, $this>
     */
    public function guildAdmins(): HasMany
    {
        return $this->hasMany(GuildAdmin::class);
    }

    public function isAdminOfGuild(int|Guild $guild): bool
    {
        $guildId = $guild instanceof Guild ? $guild->id : $guild;

        return $this->guildAdmins()->where('guild_id', $guildId)->exists();
    }

    /**
     * Whether this user's admin standing for the given guild - either tier -
     * grants access to the given dashboard section. See GuildAdmin::hasSection().
     */
    public function hasGuildAdminSection(int|Guild $guild, string $section): bool
    {
        $guildId = $guild instanceof Guild ? $guild->id : $guild;

        return $this->guildAdmins()
            ->where('guild_id', $guildId)
            ->get()
            ->contains(fn (GuildAdmin $admin) => $admin->hasSection($section));
    }
}
