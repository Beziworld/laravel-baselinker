<?php

declare(strict_types=1);

namespace Beziworld\BaseLinker\Data;

use Beziworld\BaseLinker\Support\Cast;
use Carbon\CarbonImmutable;

/**
 * Order returned by getOrders.
 */
final readonly class Order
{
    /**
     * @param list<OrderProduct> $products
     * @param array<array-key, mixed> $customExtraFields
     * @param array<string, mixed> $raw
     */
    public function __construct(
        public int $id,
        public ?int $shopOrderId,
        public ?string $externalOrderId,
        public ?string $source,
        public ?int $sourceId,
        public ?int $statusId,
        public ?CarbonImmutable $dateAdd,
        public ?CarbonImmutable $dateConfirmed,
        public ?CarbonImmutable $dateInStatus,
        public bool $confirmed,
        public ?string $userLogin,
        public ?string $currency,
        public ?string $email,
        public ?string $phone,
        public ?string $userComments,
        public ?string $adminComments,
        public OrderPayment $payment,
        public OrderDelivery $delivery,
        public Address $invoiceAddress,
        public bool $wantInvoice,
        public ?string $extraField1,
        public ?string $extraField2,
        public ?string $orderPage,
        public ?int $pickState,
        public ?int $packState,
        public ?int $star,
        public ?int $crmClientId,
        public array $customExtraFields,
        public array $products,
        public array $raw,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: Cast::int($data['order_id'] ?? null) ?? 0,
            shopOrderId: Cast::int($data['shop_order_id'] ?? null),
            externalOrderId: Cast::text($data['external_order_id'] ?? null),
            source: Cast::text($data['order_source'] ?? null),
            sourceId: Cast::int($data['order_source_id'] ?? null),
            statusId: Cast::int($data['order_status_id'] ?? null),
            dateAdd: Cast::date($data['date_add'] ?? null),
            dateConfirmed: Cast::date($data['date_confirmed'] ?? null),
            dateInStatus: Cast::date($data['date_in_status'] ?? null),
            confirmed: Cast::bool($data['confirmed'] ?? null) ?? false,
            userLogin: Cast::text($data['user_login'] ?? null),
            currency: Cast::text($data['currency'] ?? null),
            email: Cast::text($data['email'] ?? null),
            phone: Cast::text($data['phone'] ?? null),
            userComments: Cast::text($data['user_comments'] ?? null),
            adminComments: Cast::text($data['admin_comments'] ?? null),
            payment: OrderPayment::fromOrder($data),
            delivery: OrderDelivery::fromOrder($data),
            invoiceAddress: Address::fromPrefixed($data, 'invoice_'),
            wantInvoice: Cast::bool($data['want_invoice'] ?? null) ?? false,
            extraField1: Cast::text($data['extra_field_1'] ?? null),
            extraField2: Cast::text($data['extra_field_2'] ?? null),
            orderPage: Cast::text($data['order_page'] ?? null),
            pickState: Cast::int($data['pick_state'] ?? null),
            packState: Cast::int($data['pack_state'] ?? null),
            star: Cast::int($data['star'] ?? null),
            crmClientId: Cast::int($data['crm_client_id'] ?? null),
            customExtraFields: Cast::arr($data['custom_extra_fields'] ?? null),
            products: array_map(OrderProduct::fromArray(...), Cast::records($data['products'] ?? null)),
            raw: $data,
        );
    }

    /**
     * Gross value of the products.
     */
    public function productsTotalGross(): float
    {
        return round(array_sum(array_map(static fn(OrderProduct $product): float => $product->totalGross(), $this->products)), 2);
    }

    /**
     * Gross value of the products plus the delivery price.
     */
    public function totalGross(): float
    {
        return round($this->productsTotalGross() + $this->delivery->price, 2);
    }

    /**
     * Whether the recorded payment covers the products and the delivery.
     */
    public function isPaid(): bool
    {
        return $this->payment->paid + 0.005 >= $this->totalGross();
    }
}
