<?php

declare(strict_types=1);

namespace Beziworld\BaseLinker\Data;

use Beziworld\BaseLinker\Support\Cast;

/**
 * Product line of an order return.
 */
final readonly class OrderReturnProduct
{
    /**
     * @param array<string, mixed> $raw
     */
    public function __construct(
        public ?int $orderReturnProductId,
        public ?int $orderProductId,
        public ?string $productId,
        public ?string $variantId,
        public ?string $name,
        public ?string $sku,
        public ?string $ean,
        public float $priceGross,
        public float $taxRate,
        public int $quantity,
        public ?int $statusId,
        public ?int $returnReasonId,
        public ?string $returnReasonComment,
        public array $raw,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            orderReturnProductId: Cast::int($data['order_return_product_id'] ?? null),
            orderProductId: Cast::int($data['order_product_id'] ?? null),
            productId: Cast::text($data['product_id'] ?? null),
            variantId: Cast::text($data['variant_id'] ?? null),
            name: Cast::text($data['name'] ?? null),
            sku: Cast::text($data['sku'] ?? null),
            ean: Cast::text($data['ean'] ?? null),
            priceGross: Cast::float($data['price_brutto'] ?? null) ?? 0.0,
            taxRate: Cast::float($data['tax_rate'] ?? null) ?? 0.0,
            quantity: Cast::int($data['quantity'] ?? null) ?? 0,
            statusId: Cast::int($data['status_id'] ?? null),
            returnReasonId: Cast::int($data['return_reason_id'] ?? null),
            returnReasonComment: Cast::text($data['return_reason_comment'] ?? null),
            raw: $data,
        );
    }
}
