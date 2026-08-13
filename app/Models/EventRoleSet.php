<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EventRoleSet extends Model
{
    /** @use HasFactory<\Database\Factories\EventRoleSetFactory> */
    use HasFactory;

    protected $fillable = [
        'guild_id',
        'name',
        'allow_multiple_roles',
    ];

    protected function casts(): array
    {
        return [
            'allow_multiple_roles' => 'boolean',
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
     * @return HasMany<EventRole, $this>
     */
    public function roles(): HasMany
    {
        return $this->hasMany(EventRole::class)->orderBy('sort_order');
    }

    /**
     * @return HasMany<Event, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    /**
     * Whether this role set may currently be edited: it must not be
     * referenced by an occurrence that has been posted and has not yet
     * reached its scheduled start time.
     *
     * See openspec specs/event-role-sets - "Role set item management".
     */
    public function isEditable(): bool
    {
        return ! EventOccurrence::query()
            ->where('event_role_set_id', $this->id)
            ->where('status', EventOccurrence::STATUS_POSTED)
            ->where('scheduled_start_at', '>', now())
            ->exists();
    }
}
