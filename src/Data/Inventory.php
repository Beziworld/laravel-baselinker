<?php

declare(strict_types=1);

namespace Beziworld\BaseLinker\Data;

use Beziworld\BaseLinker\Support\Cast;

/**
 * Product catalog (inventory) returned by getInventories.
 */
final readonly class Inventory
{
    /**
     * @param list<string> $languages
     * @param list<int> $priceGroups
     * @param list<string> $warehouses Warehouse codes such as "bl_123" or "shop_2445".
     * @param array<string, mixed> $raw
     */
    public function __construct(
        public int $id,
        public string $name,
        public ?string $description,
        public array $languages,
        public ?string $defaultLanguage,
        public array $priceGroups,
        public ?int $defaultPriceGroup,
        public array $warehouses,
        public ?string $defaultWarehouse,
        public bool $reservations,
        public bool $isDefault,
        public array $raw,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: Cast::int($data['inventory_id'] ?? null) ?? 0,
            name: Cast::string($data['name'] ?? null) ?? '',
            description: Cast::text($data['description'] ?? null),
            languages: Cast::strings($data['languages'] ?? null),
            defaultLanguage: Cast::text($data['default_language'] ?? null),
            priceGroups: Cast::intList($data['price_groups'] ?? null),
            defaultPriceGroup: Cast::int($data['default_price_group'] ?? null),
            warehouses: Cast::strings($data['warehouses'] ?? null),
            defaultWarehouse: Cast::text($data['default_warehouse'] ?? null),
            reservations: Cast::bool($data['reservations'] ?? null) ?? false,
            isDefault: Cast::bool($data['is_default'] ?? null) ?? false,
            raw: $data,
        );
    }
}
