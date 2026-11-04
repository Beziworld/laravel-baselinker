<?php

declare(strict_types=1);

namespace Beziworld\BaseLinker\Data;

use Beziworld\BaseLinker\Support\Cast;

/**
 * Catalog product returned by getInventoryProductsList (basic fields) or getInventoryProductsData (full data).
 */
final readonly class InventoryProduct
{
    /**
     * @param list<string> $eanAdditional
     * @param list<string> $tags
     * @param array<string, float> $prices Gross prices keyed by price group id.
     * @param array<string, float> $stock Stock keyed by warehouse code (for example "bl_206").
     * @param array<string, float> $reservations Reserved quantity keyed by warehouse code.
     * @param array<array-key, mixed> $locations Locations keyed by warehouse code.
     * @param array<array-key, mixed> $textFields Text fields such as "name", "description", "name|de" or "features".
     * @param array<array-key, mixed> $images Image URLs keyed by position.
     * @param array<int, ProductVariant> $variants Variants keyed by variant id.
     * @param array<string, mixed> $raw
     */
    public function __construct(
        public int $id,
        public ?int $parentId,
        public bool $isBundle,
        public ?string $sku,
        public ?string $ean,
        public array $eanAdditional,
        public ?string $asin,
        public ?string $name,
        public array $tags,
        public ?float $taxRate,
        public ?float $weight,
        public ?float $height,
        public ?float $width,
        public ?float $length,
        public ?int $star,
        public ?int $categoryId,
        public ?int $manufacturerId,
        public array $prices,
        public array $stock,
        public array $reservations,
        public array $locations,
        public array $textFields,
        public ?float $averageCost,
        public array $images,
        public array $variants,
        public array $raw,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $textFields = Cast::arr($data['text_fields'] ?? null);
        $variants = [];

        foreach (Cast::arr($data['variants'] ?? null) as $variantId => $variant) {
            if (is_array($variant)) {
                /** @var array<string, mixed> $variant */
                $variants[(int) $variantId] = ProductVariant::fromArray($variant + ['variant_id' => (int) $variantId]);
            }
        }

        $parent = Cast::int($data['parent_id'] ?? null);

        return new self(
            id: Cast::int($data['id'] ?? $data['product_id'] ?? null) ?? 0,
            parentId: $parent !== null && $parent > 0 ? $parent : null,
            isBundle: Cast::bool($data['is_bundle'] ?? null) ?? false,
            sku: Cast::text($data['sku'] ?? null),
            ean: Cast::text($data['ean'] ?? null),
            eanAdditional: Cast::strings($data['ean_additional'] ?? null),
            asin: Cast::text($data['asin'] ?? null),
            name: Cast::text($data['name'] ?? $textFields['name'] ?? null),
            tags: Cast::strings($data['tags'] ?? null),
            taxRate: Cast::float($data['tax_rate'] ?? null),
            weight: Cast::float($data['weight'] ?? null),
            height: Cast::float($data['height'] ?? null),
            width: Cast::float($data['width'] ?? null),
            length: Cast::float($data['length'] ?? null),
            star: Cast::int($data['star'] ?? null),
            categoryId: Cast::int($data['category_id'] ?? null),
            manufacturerId: Cast::int($data['manufacturer_id'] ?? null),
            prices: Cast::floats($data['prices'] ?? null),
            stock: Cast::floats($data['stock'] ?? null),
            reservations: Cast::floats($data['reservations'] ?? null),
            locations: Cast::arr($data['locations'] ?? null),
            textFields: $textFields,
            averageCost: Cast::float($data['average_cost'] ?? null),
            images: Cast::arr($data['images'] ?? null),
            variants: $variants,
            raw: $data,
        );
    }

    /**
     * Total stock across all warehouses.
     */
    public function totalStock(): float
    {
        return array_sum($this->stock);
    }

    public function isVariant(): bool
    {
        return $this->parentId !== null;
    }
}
