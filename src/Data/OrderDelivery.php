<?php

declare(strict_types=1);

namespace Beziworld\BaseLinker\Data;

use Beziworld\BaseLinker\Support\Cast;

/**
 * Delivery details of an order: method, price, shipment and address.
 */
final readonly class OrderDelivery
{
    public function __construct(
        public ?int $methodId,
        public ?string $method,
        public float $price,
        public ?string $packageModule,
        public ?string $packageNumber,
        public Address $address,
        public ?DeliveryPoint $point,
    ) {}

    /**
     * @param array<array-key, mixed> $order
     */
    public static function fromOrder(array $order): self
    {
        return new self(
            methodId: Cast::int($order['delivery_method_id'] ?? null),
            method: Cast::text($order['delivery_method'] ?? null),
            price: Cast::float($order['delivery_price'] ?? null) ?? 0.0,
            packageModule: Cast::text($order['delivery_package_module'] ?? null),
            packageNumber: Cast::text($order['delivery_package_nr'] ?? null),
            address: Address::fromPrefixed($order, 'delivery_'),
            point: DeliveryPoint::fromOrder($order),
        );
    }
}
