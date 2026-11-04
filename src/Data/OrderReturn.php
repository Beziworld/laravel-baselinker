<?php

declare(strict_types=1);

namespace Beziworld\BaseLinker\Data;

use Beziworld\BaseLinker\Support\Cast;
use Carbon\CarbonImmutable;

/**
 * Order return returned by getOrderReturns.
 */
final readonly class OrderReturn
{
    /**
     * @param list<OrderReturnProduct> $products
     * @param array<array-key, mixed> $customExtraFields
     * @param array<string, mixed> $raw
     */
    public function __construct(
        public int $id,
        public ?int $orderId,
        public ?int $shopOrderId,
        public ?string $externalOrderId,
        public ?string $referenceNumber,
        public ?string $source,
        public ?int $sourceId,
        public ?int $statusId,
        public ?CarbonImmutable $dateAdd,
        public ?CarbonImmutable $dateInStatus,
        public ?string $userLogin,
        public ?string $currency,
        public bool $refunded,
        public ?string $email,
        public ?string $phone,
        public float $deliveryPrice,
        public Address $deliveryAddress,
        public ?string $packageModule,
        public ?string $packageNumber,
        public ?string $adminComments,
        public ?string $extraField1,
        public ?string $extraField2,
        public array $customExtraFields,
        public ?string $refundAccountNumber,
        public ?string $refundIban,
        public ?string $refundSwift,
        public ?int $fulfillmentStatus,
        public array $products,
        public array $raw,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: Cast::int($data['return_id'] ?? null) ?? 0,
            orderId: Cast::int($data['order_id'] ?? null),
            shopOrderId: Cast::int($data['shop_order_id'] ?? null),
            externalOrderId: Cast::text($data['external_order_id'] ?? null),
            referenceNumber: Cast::text($data['reference_number'] ?? null),
            source: Cast::text($data['order_return_source'] ?? null),
            sourceId: Cast::int($data['order_return_source_id'] ?? null),
            statusId: Cast::int($data['status_id'] ?? null),
            dateAdd: Cast::date($data['date_add'] ?? null),
            dateInStatus: Cast::date($data['date_in_status'] ?? null),
            userLogin: Cast::text($data['user_login'] ?? null),
            currency: Cast::text($data['currency'] ?? null),
            refunded: Cast::bool($data['refunded'] ?? null) ?? false,
            email: Cast::text($data['email'] ?? null),
            phone: Cast::text($data['phone'] ?? null),
            deliveryPrice: Cast::float($data['delivery_price'] ?? null) ?? 0.0,
            deliveryAddress: Address::fromPrefixed($data, 'delivery_'),
            packageModule: Cast::text($data['delivery_package_module'] ?? null),
            packageNumber: Cast::text($data['delivery_package_nr'] ?? null),
            adminComments: Cast::text($data['admin_comments'] ?? null),
            extraField1: Cast::text($data['extra_field_1'] ?? null),
            extraField2: Cast::text($data['extra_field_2'] ?? null),
            customExtraFields: Cast::arr($data['custom_extra_fields'] ?? null),
            refundAccountNumber: Cast::text($data['order_return_account_number'] ?? null),
            refundIban: Cast::text($data['order_return_iban'] ?? null),
            refundSwift: Cast::text($data['order_return_swift'] ?? null),
            fulfillmentStatus: Cast::int($data['fulfillment_status'] ?? null),
            products: array_map(OrderReturnProduct::fromArray(...), Cast::records($data['products'] ?? null)),
            raw: $data,
        );
    }
}
