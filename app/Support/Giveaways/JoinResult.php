<?php

declare(strict_types=1);

namespace App\Support\Giveaways;

use App\Models\CollectionThemeItem;

/**
 * The outcome of a join attempt, matching the `won` / `already_entered` /
 * `expired` variants documented in openapi.yaml's EntryResult schema.
 */
final class JoinResult
{
    public const STATUS_WON = 'won';

    public const STATUS_ALREADY_ENTERED = 'already_entered';

    public const STATUS_EXPIRED = 'expired';

    private function __construct(
        public readonly string $status,
        public readonly ?CollectionThemeItem $item = null,
    ) {}

    public static function won(CollectionThemeItem $item): self
    {
        return new self(self::STATUS_WON, $item);
    }

    public static function alreadyEntered(?CollectionThemeItem $item): self
    {
        return new self(self::STATUS_ALREADY_ENTERED, $item);
    }

    public static function expired(): self
    {
        return new self(self::STATUS_EXPIRED);
    }

    /**
     * @return array{status: string, item?: array{id: int, name: string}|null}
     */
    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'item' => $this->item ? ['id' => $this->item->id, 'name' => $this->item->name] : null,
        ];
    }
}
