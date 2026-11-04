<?php

declare(strict_types=1);

namespace Beziworld\BaseLinker\Data;

use Beziworld\BaseLinker\Enums\TrackingStatus;
use Beziworld\BaseLinker\Support\Cast;
use Carbon\CarbonImmutable;

/**
 * Shipment of an order returned by getOrderPackages.
 */
final readonly class Package
{
    public const int TYPE_STANDARD = 0;

    public const int TYPE_RETURN = 1;

    public const int TYPE_SENT_AT_POINT = 2;

    public const int TYPE_RETURN_SENT_AT_POINT = 3;

    /**
     * @param array<string, mixed> $raw
     */
    public function __construct(
        public int $id,
        public ?string $packageNumber,
        public ?string $courierInnerNumber,
        public ?string $courierCode,
        public ?string $courierOtherName,
        public ?int $accountId,
        public ?TrackingStatus $trackingStatus,
        public ?CarbonImmutable $trackingStatusDate,
        public ?int $trackingDeliveryDays,
        public ?int $packageType,
        public ?string $trackingUrl,
        public bool $isReturn,
        public array $raw,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $status = Cast::int($data['tracking_status'] ?? null);

        return new self(
            id: Cast::int($data['package_id'] ?? null) ?? 0,
            packageNumber: Cast::text($data['courier_package_nr'] ?? null),
            courierInnerNumber: Cast::text($data['courier_inner_number'] ?? null),
            courierCode: Cast::text($data['courier_code'] ?? null),
            courierOtherName: Cast::text($data['courier_other_name'] ?? null),
            accountId: Cast::int($data['account_id'] ?? null),
            trackingStatus: $status !== null ? TrackingStatus::tryFrom($status) : null,
            trackingStatusDate: Cast::date($data['tracking_status_date'] ?? null),
            trackingDeliveryDays: Cast::int($data['tracking_delivery_days'] ?? null),
            packageType: Cast::int($data['package_type'] ?? null),
            trackingUrl: Cast::text($data['tracking_url'] ?? null),
            isReturn: Cast::bool($data['is_return'] ?? null) ?? false,
            raw: $data,
        );
    }
}
