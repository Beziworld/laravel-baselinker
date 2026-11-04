<?php

declare(strict_types=1);

namespace Beziworld\BaseLinker\Resources;

use Beziworld\BaseLinker\Data\File;
use Beziworld\BaseLinker\Data\Invoice;
use Illuminate\Support\LazyCollection;

/**
 * Invoices, invoice corrections, invoice files and numbering series.
 *
 * Every method maps one-to-one to the BaseLinker API method of the same name.
 */
final class Invoices extends Resource
{
    /**
     * Issues an order invoice.
     *
     * @param int $orderId Order Identifier from Base order manager
     * @param int $seriesId Series numbering identifier
     * @param array<string, mixed> $options Optional fields: vat_rate.
     * @see https://api.baselinker.com/index.php?method=addInvoice
     */
    public function addInvoice(int $orderId, int $seriesId, array $options = []): ?int
    {
        $response = $this->client->call('addInvoice', ['order_id' => $orderId, 'series_id' => $seriesId] + $options);

        return $this->int($response, 'invoice_id');
    }

    /**
     * Issues an order invoice correction.
     *
     * @param array<string, mixed> $correction Fields: original_invoice_id, return_order_id, series_id, date_sell, correcting_reason, correcting_items, correcting_data, invoice_fullname, invoice_company, invoice_address, invoice_postcode, invoice_city, invoice_state, invoice_country_code, invoice_nip, items, fv_payment, fv_person.
     * @see https://api.baselinker.com/index.php?method=addInvoiceCorrection
     */
    public function addInvoiceCorrection(array $correction): ?int
    {
        $response = $this->client->call('addInvoiceCorrection', $correction);

        return $this->int($response, 'invoice_id');
    }

    /**
     * Adds an external file to an invoice previously issued from Base.
     *
     * @param int $invoiceId Base invoice identifier
     * @param string $contents Binary file contents; encoded as the API expects.
     * @param string $externalInvoiceNumber External system invoice number (overwrites Base invoice number)
     * @see https://api.baselinker.com/index.php?method=addOrderInvoiceFile
     */
    public function addOrderInvoiceFile(int $invoiceId, string $contents, string $externalInvoiceNumber): void
    {
        $this->client->call('addOrderInvoiceFile', ['invoice_id' => $invoiceId, 'file' => $this->encodeFile($contents), 'external_invoice_number' => $externalInvoiceNumber]);
    }

    /**
     * Gets the invoice file from Base.
     *
     * @param int $invoiceId Base invoice identifier
     * @param array<string, mixed> $options Optional fields: get_external.
     * @see https://api.baselinker.com/index.php?method=getInvoiceFile
     */
    public function getInvoiceFile(int $invoiceId, array $options = []): File
    {
        $response = $this->client->call('getInvoiceFile', ['invoice_id' => $invoiceId] + $options);

        return File::fromResponse($response, 'invoice', 'invoice_number');
    }

    /**
     * Downloads invoices issued from the Base order manager.
     *
     * @param array<string, mixed> $filters Filters: invoice_id, order_id, date_from, id_from, series_id, get_external_invoices, get_government_data.
     * @return list<Invoice>
     * @see https://api.baselinker.com/index.php?method=getInvoices
     */
    public function getInvoices(array $filters = []): array
    {
        $response = $this->client->call('getInvoices', $filters);

        return array_map(Invoice::fromArray(...), $this->items($response, 'invoices'));
    }

    /**
     * Retrieves invoice and receipt numbering series.
     *
     * @return list<array<string, mixed>>
     * @see https://api.baselinker.com/index.php?method=getSeries
     */
    public function getSeries(): array
    {
        $response = $this->client->call('getSeries');

        return $this->items($response, 'series');
    }

    /**
     * Maximum number of invoices returned by one getInvoices call.
     */
    public const int PAGE_SIZE = 100;

    /**
     * Lazily iterates invoices by ascending id, starting at the given invoice id.
     *
     * @param array<string, mixed> $filters Other getInvoices filters, for example series_id or get_external_invoices.
     * @return LazyCollection<int, Invoice>
     */
    public function iterate(int $idFrom = 1, array $filters = []): LazyCollection
    {
        return LazyCollection::make(function () use ($idFrom, $filters) {
            $cursor = $idFrom;

            do {
                $invoices = $this->getInvoices(['id_from' => $cursor] + $filters);

                foreach ($invoices as $invoice) {
                    yield $invoice;
                }

                if ($invoices === []) {
                    return;
                }

                $cursor = max($cursor, $invoices[array_key_last($invoices)]->id) + 1;
            } while (count($invoices) >= self::PAGE_SIZE);
        });
    }
}
