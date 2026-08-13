<?php

declare(strict_types=1);

namespace App\Support\StandardGiveaways;

/**
 * The outcome of a standard giveaway entry attempt.
 */
final class StandardGiveawayEntryResult
{
    public const STATUS_ENTERED = 'entered';

    public const STATUS_ALREADY_ENTERED = 'already_entered';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_CLOSED = 'closed';

    private function __construct(
        public readonly string $status,
        public readonly ?string $reason = null,
    ) {}

    public static function entered(): self
    {
        return new self(self::STATUS_ENTERED);
    }

    public static function alreadyEntered(): self
    {
        return new self(self::STATUS_ALREADY_ENTERED);
    }

    public static function rejected(string $reason): self
    {
        return new self(self::STATUS_REJECTED, $reason);
    }

    public static function closed(): self
    {
        return new self(self::STATUS_CLOSED);
    }

    /**
     * @return array{status: string, reason?: string|null}
     */
    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'reason' => $this->reason,
        ];
    }
}
