<?php

declare(strict_types=1);

namespace Beziworld\BaseLinker\Data;

use Beziworld\BaseLinker\Support\Cast;
use Carbon\CarbonImmutable;

/**
 * Invoice returned by getInvoices.
 */
final readonly class Invoice
{
    /**
     * @param list<array<string, mixed>> $items
     * @param array<string, mixed> $raw
     */
    public function __construct(
        public int $id,
        public ?int $orderId,
        public ?int $seriesId,
        public ?string $type,
        public ?string $number,
        public ?CarbonImmutable $dateAdd,
        public ?CarbonImmutable $dateSell,
        public ?CarbonImmutable $datePayTo,
        public ?string $currency,
        public float $totalGross,
        public float $totalNet,
        public ?string $payment,
        public ?string $additionalInfo,
        public Address $buyer,
        public ?string $seller,
        public ?string $issuer,
        public ?int $correctingToInvoiceId,
        public ?string $correctingReason,
        public ?string $externalInvoiceNumber,
        public ?string $exchangeCurrency,
        public ?float $exchangeRate,
        public array $items,
        public array $raw,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $correcting = Cast::int($data['correcting_to_invoice_id'] ?? null);

        return new self(
            id: Cast::int($data['invoice_id'] ?? null) ?? 0,
            orderId: Cast::int($data['order_id'] ?? null),
            seriesId: Cast::int($data['series_id'] ?? null),
            type: Cast::text($data['type'] ?? null),
            number: Cast::text($data['number'] ?? null),
            dateAdd: Cast::date($data['date_add'] ?? null),
            dateSell: Cast::date($data['date_sell'] ?? null),
            datePayTo: Cast::date($data['date_pay_to'] ?? null),
            currency: Cast::text($data['currency'] ?? null),
            totalGross: Cast::float($data['total_price_brutto'] ?? null) ?? 0.0,
            totalNet: Cast::float($data['total_price_netto'] ?? null) ?? 0.0,
            payment: Cast::text($data['payment'] ?? null),
            additionalInfo: Cast::text($data['additional_info'] ?? null),
            buyer: Address::fromPrefixed($data, 'invoice_'),
            seller: Cast::text($data['seller'] ?? null),
            issuer: Cast::text($data['issuer'] ?? null),
            correctingToInvoiceId: $correcting !== null && $correcting > 0 ? $correcting : null,
            correctingReason: Cast::text($data['correcting_reason'] ?? null),
            externalInvoiceNumber: Cast::text($data['external_invoice_number'] ?? null),
            exchangeCurrency: Cast::text($data['exchange_currency'] ?? null),
            exchangeRate: Cast::float($data['exchange_rate'] ?? null),
            items: Cast::records($data['items'] ?? null),
            raw: $data,
        );
    }

    public function isCorrection(): bool
    {
        return $this->type === 'correcting' || $this->correctingToInvoiceId !== null;
    }
}
