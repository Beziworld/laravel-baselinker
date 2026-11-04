<?php

declare(strict_types=1);

namespace Beziworld\BaseLinker\Data;

use Beziworld\BaseLinker\Support\Cast;

/**
 * Payment details of an order.
 */
final readonly class OrderPayment
{
    public function __construct(
        public ?string $method,
        public bool $cashOnDelivery,
        public float $paid,
        public ?string $currency,
    ) {}

    /**
     * @param array<array-key, mixed> $order
     */
    public static function fromOrder(array $order): self
    {
        return new self(
            method: Cast::text($order['payment_method'] ?? null),
            cashOnDelivery: Cast::bool($order['payment_method_cod'] ?? null) ?? false,
            paid: Cast::float($order['payment_done'] ?? null) ?? 0.0,
            currency: Cast::text($order['currency'] ?? null),
        );
    }
}
