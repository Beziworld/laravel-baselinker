<?php

declare(strict_types=1);

namespace Beziworld\BaseLinker\Data;

use Beziworld\BaseLinker\Support\Cast;

/**
 * Delivery or invoice address of an order, a return or an invoice.
 */
final readonly class Address
{
    public function __construct(
        public ?string $fullName,
        public ?string $company,
        public ?string $address,
        public ?string $postcode,
        public ?string $city,
        public ?string $state,
        public ?string $country,
        public ?string $countryCode,
        public ?string $taxId = null,
    ) {}

    /**
     * Reads the fields sharing a prefix, for example "delivery_" or "invoice_".
     *
     * @param array<array-key, mixed> $data
     */
    public static function fromPrefixed(array $data, string $prefix): self
    {
        return new self(
            fullName: Cast::text($data[$prefix . 'fullname'] ?? null),
            company: Cast::text($data[$prefix . 'company'] ?? null),
            address: Cast::text($data[$prefix . 'address'] ?? null),
            postcode: Cast::text($data[$prefix . 'postcode'] ?? null),
            city: Cast::text($data[$prefix . 'city'] ?? null),
            state: Cast::text($data[$prefix . 'state'] ?? null),
            country: Cast::text($data[$prefix . 'country'] ?? null),
            countryCode: Cast::text($data[$prefix . 'country_code'] ?? null),
            taxId: Cast::text($data[$prefix . 'nip'] ?? $data[$prefix . 'tax_id'] ?? null),
        );
    }

    public function isEmpty(): bool
    {
        return $this->fullName === null && $this->company === null && $this->address === null && $this->city === null;
    }
}
