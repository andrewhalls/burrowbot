<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventRole extends Model
{
    /** @use HasFactory<\Database\Factories\EventRoleFactory> */
    use HasFactory;

    public const CAPACITY_UNCAPPED = 'uncapped';

    public const CAPACITY_CAPPED = 'capped';

    public const CAPACITY_WAITLISTED = 'waitlisted';

    protected $fillable = [
        'event_role_set_id',
        'name',
        'sort_order',
        'capacity_mode',
        'capacity',
    ];

    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<EventRoleSet, $this>
     */
    public function eventRoleSet(): BelongsTo
    {
        return $this->belongsTo(EventRoleSet::class);
    }

    public function isUncapped(): bool
    {
        return $this->capacity_mode === self::CAPACITY_UNCAPPED;
    }

    public function isWaitlisted(): bool
    {
        return $this->capacity_mode === self::CAPACITY_WAITLISTED;
    }
}
