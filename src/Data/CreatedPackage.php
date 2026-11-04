<?php

declare(strict_types=1);

namespace Beziworld\BaseLinker\Data;

use Beziworld\BaseLinker\Support\Cast;

/**
 * Shipment created with createPackage or createPackageManual.
 *
 * For multi-parcel shipments "packages" lists every parcel with its own
 * package_id, package_number and courier_inner_number.
 */
final readonly class CreatedPackage
{
    /**
     * @param list<array<string, mixed>> $packages
     * @param array<string, mixed> $raw
     */
    public function __construct(
        public int $packageId,
        public ?string $packageNumber,
        public ?string $courierInnerNumber,
        public array $packages,
        public array $raw,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            packageId: Cast::int($data['package_id'] ?? null) ?? 0,
            packageNumber: Cast::text($data['package_number'] ?? null),
            courierInnerNumber: Cast::text($data['courier_inner_number'] ?? null),
            packages: Cast::records($data['packages'] ?? null),
            raw: $data,
        );
    }
}
