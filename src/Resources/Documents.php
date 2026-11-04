<?php

declare(strict_types=1);

namespace Beziworld\BaseLinker\Resources;

use Beziworld\BaseLinker\Data\File;

/**
 * Inventory (warehouse) documents.
 *
 * Every method maps one-to-one to the BaseLinker API method of the same name.
 */
final class Documents extends Resource
{
    /**
     * Creates a new inventory document in Base storage.
     *
     * @param int $warehouseId Source warehouse identifier
     * @param int $documentType Document type: 0 - GR (Goods Received), 1 - IGR (Internal Goods Received), 2 - GI (Goods Issue), 3 - IGI (Internal Goods Issue), 4 - IT (Internal...
     * @param array<string, mixed> $options Optional fields: target_warehouse_id, date_add, date_execute, contractor, invoice_no, notes.
     * @return array<string, mixed>
     * @see https://api.baselinker.com/index.php?method=addInventoryDocument
     */
    public function addInventoryDocument(int $warehouseId, int $documentType, array $options = []): array
    {
        return $this->client->call('addInventoryDocument', ['warehouse_id' => $warehouseId, 'document_type' => $documentType] + $options);
    }

    /**
     * Adds an external PDF file to a warehouse document.
     *
     * @param int $documentId Warehouse document identifier from Base
     * @param string $contents Binary file contents; encoded as the API expects.
     * @param array<string, mixed> $options Optional fields: external_document_number.
     * @see https://api.baselinker.com/index.php?method=addInventoryDocumentFile
     */
    public function addInventoryDocumentFile(int $documentId, string $contents, array $options = []): void
    {
        $this->client->call('addInventoryDocumentFile', ['document_id' => $documentId, 'file' => $this->encodeFile($contents)] + $options);
    }

    /**
     * Adds items to an existing inventory document, or updates the quantity and price of an existing item by item_id.
     *
     * @param int $documentId Document identifier
     * @param array<array-key, mixed> $items API field "items". List of document items to add or update
     * @return list<array<string, mixed>>
     * @see https://api.baselinker.com/index.php?method=addInventoryDocumentItems
     */
    public function addInventoryDocumentItems(int $documentId, array $items): array
    {
        $response = $this->client->call('addInventoryDocumentItems', ['document_id' => $documentId, 'items' => $items]);

        return $this->items($response, 'items');
    }

    /**
     * Downloads a warehouse document PDF file.
     *
     * @param int $documentId Warehouse document identifier from Base
     * @param array<string, mixed> $options Optional fields: get_external.
     * @see https://api.baselinker.com/index.php?method=getInventoryDocumentFile
     */
    public function getInventoryDocumentFile(int $documentId, array $options = []): File
    {
        $response = $this->client->call('getInventoryDocumentFile', ['document_id' => $documentId] + $options);

        return File::fromResponse($response, 'file', 'document_number');
    }

    /**
     * Retrieves document items for a specific inventory document in Base.
     *
     * @param int $documentId Document identifier
     * @param array<string, mixed> $options Optional fields: page.
     * @return list<array<string, mixed>>
     * @see https://api.baselinker.com/index.php?method=getInventoryDocumentItems
     */
    public function getInventoryDocumentItems(int $documentId, array $options = []): array
    {
        $response = $this->client->call('getInventoryDocumentItems', ['document_id' => $documentId] + $options);

        return $this->items($response, 'items');
    }

    /**
     * Retrieves information about available inventory document series in Base.
     *
     * @param array<string, mixed> $filters Filters: warehouse_id.
     * @return list<array<string, mixed>>
     * @see https://api.baselinker.com/index.php?method=getInventoryDocumentSeries
     */
    public function getInventoryDocumentSeries(array $filters = []): array
    {
        $response = $this->client->call('getInventoryDocumentSeries', $filters);

        return $this->items($response, 'document_series');
    }

    /**
     * Retrieves a list of inventory documents from Base.
     *
     * @param array<string, mixed> $filters Filters: filter_source_object_type, filter_source_object_id, filter_document_id, filter_document_type, filter_document_status, filter_date_from, filter_date_to, filter_warehouse_id, page.
     * @return list<array<string, mixed>>
     * @see https://api.baselinker.com/index.php?method=getInventoryDocuments
     */
    public function getInventoryDocuments(array $filters = []): array
    {
        $response = $this->client->call('getInventoryDocuments', $filters);

        return $this->items($response, 'documents');
    }

    /**
     * Confirms an inventory document, which will affect the stock levels in the warehouse.
     *
     * @param int $documentId Document identifier
     * @see https://api.baselinker.com/index.php?method=setInventoryDocumentStatusConfirmed
     */
    public function setInventoryDocumentStatusConfirmed(int $documentId): void
    {
        $this->client->call('setInventoryDocumentStatusConfirmed', ['document_id' => $documentId]);
    }
}
