<?php

declare(strict_types=1);

namespace Beziworld\BaseLinker\Data;

use Beziworld\BaseLinker\Support\Cast;

/**
 * Order (or order return) status defined in the order manager.
 */
final readonly class OrderStatus
{
    /**
     * @param array<string, mixed> $raw
     */
    public function __construct(
        public int $id,
        public string $name,
        public ?string $color,
        public ?int $groupId,
        public bool $isPrimary,
        public ?string $nameForCustomer,
        public array $raw,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: Cast::int($data['id'] ?? null) ?? 0,
            name: Cast::string($data['name'] ?? null) ?? '',
            color: Cast::text($data['color'] ?? null),
            groupId: Cast::int($data['group_id'] ?? null),
            isPrimary: Cast::bool($data['is_primary'] ?? null) ?? false,
            nameForCustomer: Cast::text($data['name_for_customer'] ?? null),
            raw: $data,
        );
    }
}
