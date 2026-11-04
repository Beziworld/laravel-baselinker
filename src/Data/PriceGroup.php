<?php

declare(strict_types=1);

namespace Beziworld\BaseLinker\Data;

use Beziworld\BaseLinker\Support\Cast;

/**
 * Price group returned by getInventoryPriceGroups.
 */
final readonly class PriceGroup
{
    /**
     * @param array<string, mixed> $raw
     */
    public function __construct(
        public int $id,
        public string $name,
        public ?string $description,
        public ?string $currency,
        public bool $isDefault,
        public array $raw,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: Cast::int($data['price_group_id'] ?? null) ?? 0,
            name: Cast::string($data['name'] ?? null) ?? '',
            description: Cast::text($data['description'] ?? null),
            currency: Cast::text($data['currency'] ?? null),
            isDefault: Cast::bool($data['is_default'] ?? null) ?? false,
            raw: $data,
        );
    }
}
