<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StandardGiveawayRequiredRole extends Model
{
    /** @use HasFactory<\Database\Factories\StandardGiveawayRequiredRoleFactory> */
    use HasFactory;

    protected $fillable = [
        'standard_giveaway_id',
        'discord_role_id',
    ];

    /**
     * @return BelongsTo<StandardGiveaway, $this>
     */
    public function standardGiveaway(): BelongsTo
    {
        return $this->belongsTo(StandardGiveaway::class);
    }
}
