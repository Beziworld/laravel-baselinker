<?php

declare(strict_types=1);

namespace Beziworld\BaseLinker\Data;

use Beziworld\BaseLinker\Support\Cast;

/**
 * Pickup point (parcel locker, shop) selected for an order delivery.
 */
final readonly class DeliveryPoint
{
    public function __construct(
        public string $id,
        public ?string $name,
        public ?string $address,
        public ?string $postcode,
        public ?string $city,
    ) {}

    /**
     * Reads the delivery_point_* fields of an order; null when no point is set.
     *
     * @param array<array-key, mixed> $order
     */
    public static function fromOrder(array $order): ?self
    {
        $id = Cast::text($order['delivery_point_id'] ?? null);

        return $id === null ? null : new self(
            id: $id,
            name: Cast::text($order['delivery_point_name'] ?? null),
            address: Cast::text($order['delivery_point_address'] ?? null),
            postcode: Cast::text($order['delivery_point_postcode'] ?? null),
            city: Cast::text($order['delivery_point_city'] ?? null),
        );
    }
}
