<?php

declare(strict_types=1);

namespace Beziworld\BaseLinker\Data;

use Beziworld\BaseLinker\Support\Cast;

/**
 * Warehouse returned by getInventoryWarehouses.
 *
 * Type "bl" is a Base warehouse; "shop" and "warehouse" are created
 * automatically to keep stock of connected shops and wholesalers.
 */
final readonly class Warehouse
{
    /**
     * @param array<string, mixed> $raw
     */
    public function __construct(
        public string $type,
        public int $id,
        public ?int $internalId,
        public string $name,
        public ?string $description,
        public bool $stockEdition,
        public bool $isDefault,
        public ?string $address,
        public ?string $postcode,
        public ?string $city,
        public ?string $country,
        public array $raw,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            type: Cast::text($data['warehouse_type'] ?? null) ?? 'bl',
            id: Cast::int($data['warehouse_id'] ?? null) ?? 0,
            internalId: Cast::int($data['internal_warehouse_id'] ?? null),
            name: Cast::string($data['name'] ?? null) ?? '',
            description: Cast::text($data['description'] ?? null),
            stockEdition: Cast::bool($data['stock_edition'] ?? null) ?? false,
            isDefault: Cast::bool($data['is_default'] ?? null) ?? false,
            address: Cast::text($data['address'] ?? null),
            postcode: Cast::text($data['postcode'] ?? null),
            city: Cast::text($data['city'] ?? null),
            country: Cast::text($data['country'] ?? null),
            raw: $data,
        );
    }

    /**
     * Identifier used in stock maps and updates, for example "bl_123".
     */
    public function code(): string
    {
        return $this->type . '_' . $this->id;
    }
}
