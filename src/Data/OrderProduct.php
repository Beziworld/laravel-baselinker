<?php

declare(strict_types=1);

namespace Beziworld\BaseLinker\Data;

use Beziworld\BaseLinker\Support\Cast;

/**
 * Product line of an order.
 */
final readonly class OrderProduct
{
    /**
     * @param array<string, mixed> $raw
     */
    public function __construct(
        public ?int $orderProductId,
        public ?string $storage,
        public ?string $storageId,
        public ?string $productId,
        public ?string $variantId,
        public ?string $name,
        public ?string $sku,
        public ?string $ean,
        public ?string $location,
        public ?int $warehouseId,
        public ?string $auctionId,
        public ?string $attributes,
        public float $priceGross,
        public float $taxRate,
        public int $quantity,
        public float $weight,
        public ?int $bundleId,
        public array $raw,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            orderProductId: Cast::int($data['order_product_id'] ?? null),
            storage: Cast::text($data['storage'] ?? null),
            storageId: Cast::text($data['storage_id'] ?? null),
            productId: Cast::text($data['product_id'] ?? null),
            variantId: Cast::text($data['variant_id'] ?? null),
            name: Cast::text($data['name'] ?? null),
            sku: Cast::text($data['sku'] ?? null),
            ean: Cast::text($data['ean'] ?? null),
            location: Cast::text($data['location'] ?? null),
            warehouseId: Cast::int($data['warehouse_id'] ?? null),
            auctionId: Cast::text($data['auction_id'] ?? null),
            attributes: Cast::text($data['attributes'] ?? null),
            priceGross: Cast::float($data['price_brutto'] ?? null) ?? 0.0,
            taxRate: Cast::float($data['tax_rate'] ?? null) ?? 0.0,
            quantity: Cast::int($data['quantity'] ?? null) ?? 0,
            weight: Cast::float($data['weight'] ?? null) ?? 0.0,
            bundleId: Cast::int($data['bundle_id'] ?? null),
            raw: $data,
        );
    }

    /**
     * Gross value of the line (unit price times quantity).
     */
    public function totalGross(): float
    {
        return round($this->priceGross * $this->quantity, 2);
    }
}
