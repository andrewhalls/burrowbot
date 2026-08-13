<?php

declare(strict_types=1);

namespace App\Support\Events;

use App\Models\EventRole;

/**
 * The outcome of a signup attempt on an event occurrence.
 */
final class SignupResult
{
    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_WAITLISTED = 'waitlisted';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_NOT_ATTENDING = 'not_attending';

    private function __construct(
        public readonly string $status,
        public readonly ?EventRole $role = null,
        public readonly ?string $reason = null,
    ) {}

    public static function confirmed(EventRole $role): self
    {
        return new self(self::STATUS_CONFIRMED, $role);
    }

    public static function waitlisted(EventRole $role): self
    {
        return new self(self::STATUS_WAITLISTED, $role);
    }

    public static function rejected(string $reason): self
    {
        return new self(self::STATUS_REJECTED, null, $reason);
    }

    public static function notAttending(): self
    {
        return new self(self::STATUS_NOT_ATTENDING);
    }

    /**
     * @return array{status: string, role?: array{id: int, name: string}|null, reason?: string|null}
     */
    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'role' => $this->role ? ['id' => $this->role->id, 'name' => $this->role->name] : null,
            'reason' => $this->reason,
        ];
    }
}
