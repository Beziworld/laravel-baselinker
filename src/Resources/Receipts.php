<?php

declare(strict_types=1);

namespace Beziworld\BaseLinker\Resources;

/**
 * Receipts and fiscal printer integration.
 *
 * Every method maps one-to-one to the BaseLinker API method of the same name.
 */
final class Receipts extends Resource
{
    /**
     * Adds an external file to a receipt previously issued from Base.
     *
     * @param int $receiptId Base receipt identifier
     * @param string $contents Binary file contents; encoded as the API expects.
     * @param string $externalReceiptNumber External system receipt number (overwrites Base receipt number)
     * @see https://api.baselinker.com/index.php?method=addOrderReceiptFile
     */
    public function addOrderReceiptFile(int $receiptId, string $contents, string $externalReceiptNumber): void
    {
        $this->client->call('addOrderReceiptFile', ['receipt_id' => $receiptId, 'file' => $this->encodeReceiptFile($contents), 'external_receipt_number' => $externalReceiptNumber]);
    }

    /**
     * Issues a receipt for an order.
     *
     * @param int $orderId Order identifier
     * @param array<string, mixed> $options Optional fields: series_id.
     * @see https://api.baselinker.com/index.php?method=addReceipt
     */
    public function addReceipt(int $orderId, array $options = []): ?int
    {
        $response = $this->client->call('addReceipt', ['order_id' => $orderId] + $options);

        return $this->int($response, 'receipt_id');
    }

    /**
     * Retrieves receipts waiting to be issued.
     *
     * @param array<string, mixed> $filters Filters: series_id, id_from, device_id.
     * @return list<array<string, mixed>>
     * @see https://api.baselinker.com/index.php?method=getNewReceipts
     */
    public function getNewReceipts(array $filters = []): array
    {
        $response = $this->client->call('getNewReceipts', $filters);

        return $this->items($response, 'orders');
    }

    /**
     * Retrieves a single receipt from the Base order manager.
     *
     * @param array<string, mixed> $filters Filters: receipt_id, order_id.
     * @return array<string, mixed>
     * @see https://api.baselinker.com/index.php?method=getReceipt
     */
    public function getReceipt(array $filters = []): array
    {
        return $this->client->call('getReceipt', $filters);
    }

    /**
     * Retrieves issued receipts.
     *
     * @param array<string, mixed> $filters Filters: series_id, id_from, date_from, date_to.
     * @return list<array<string, mixed>>
     * @see https://api.baselinker.com/index.php?method=getReceipts
     */
    public function getReceipts(array $filters = []): array
    {
        $response = $this->client->call('getReceipts', $filters);

        return $this->items($response, 'receipts');
    }

    /**
     * Marks orders with a receipt already issued.
     *
     * @param int $receiptId Receipt_id number received in the getNewReceipts method
     * @param string $receiptNr The number of the issued receipt (may be blank if the printer does not return the number)
     * @param int $date Receipt printing date (unixtime format)
     * @param array<string, mixed> $options Optional fields: printer_error, printer_name.
     * @see https://api.baselinker.com/index.php?method=setOrderReceipt
     */
    public function setOrderReceipt(int $receiptId, string $receiptNr, int $date, array $options = []): void
    {
        $this->client->call('setOrderReceipt', ['receipt_id' => $receiptId, 'receipt_nr' => $receiptNr, 'date' => $date] + $options);
    }
}
