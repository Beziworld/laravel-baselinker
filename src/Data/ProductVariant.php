<?php

declare(strict_types=1);

namespace Beziworld\BaseLinker\Data;

use Beziworld\BaseLinker\Support\Cast;

/**
 * Variant of a catalog product.
 */
final readonly class ProductVariant
{
    /**
     * @param array<string, float> $prices Gross prices keyed by price group id.
     * @param array<string, float> $stock Stock keyed by warehouse code (for example "bl_206").
     * @param array<array-key, mixed> $locations Locations keyed by warehouse code.
     * @param array<string, mixed> $raw
     */
    public function __construct(
        public int $id,
        public ?string $name,
        public ?string $sku,
        public ?string $ean,
        public array $prices,
        public array $stock,
        public array $locations,
        public array $raw,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: Cast::int($data['variant_id'] ?? $data['id'] ?? null) ?? 0,
            name: Cast::text($data['name'] ?? null),
            sku: Cast::text($data['sku'] ?? null),
            ean: Cast::text($data['ean'] ?? null),
            prices: Cast::floats($data['prices'] ?? null),
            stock: Cast::floats($data['stock'] ?? null),
            locations: Cast::arr($data['locations'] ?? null),
            raw: $data,
        );
    }
}
