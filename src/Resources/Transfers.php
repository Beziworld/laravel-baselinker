<?php

declare(strict_types=1);

namespace Beziworld\BaseLinker\Resources;

/**
 * Inventory transfers between warehouses.
 *
 * Every method maps one-to-one to the BaseLinker API method of the same name.
 */
final class Transfers extends Resource
{
    /**
     * Creates a new inventory transfer in draft status.
     *
     * @param int $warehouseId Source warehouse identifier
     * @param int $targetWarehouseId Target warehouse identifier
     * @param array<string, mixed> $options Optional fields: transfer_type, name, priority, assigned_to, date_scheduled, date_due, notes.
     * @return array<string, mixed>
     * @see https://api.baselinker.com/index.php?method=addInventoryTransfer
     */
    public function addInventoryTransfer(int $warehouseId, int $targetWarehouseId, array $options = []): array
    {
        return $this->client->call('addInventoryTransfer', ['warehouse_id' => $warehouseId, 'target_warehouse_id' => $targetWarehouseId] + $options);
    }

    /**
     * Adds planning items to an inventory transfer in draft status, or updates the quantity of an existing item by item_id.
     *
     * @param int $transferId Transfer identifier
     * @param array<array-key, mixed> $items API field "items". List of items to add or update
     * @return list<array<string, mixed>>
     * @see https://api.baselinker.com/index.php?method=addInventoryTransferItems
     */
    public function addInventoryTransferItems(int $transferId, array $items): array
    {
        $response = $this->client->call('addInventoryTransferItems', ['transfer_id' => $transferId, 'items' => $items]);

        return $this->items($response, 'items');
    }

    /**
     * Retrieves items from a specific inventory transfer.
     *
     * @param int $transferId Transfer identifier
     * @param array<string, mixed> $options Optional fields: page.
     * @return list<array<string, mixed>>
     * @see https://api.baselinker.com/index.php?method=getInventoryTransferItems
     */
    public function getInventoryTransferItems(int $transferId, array $options = []): array
    {
        $response = $this->client->call('getInventoryTransferItems', ['transfer_id' => $transferId] + $options);

        return $this->items($response, 'items');
    }

    /**
     * Retrieves a list of available transfer numbering series.
     *
     * @param array<string, mixed> $filters Filters: warehouse_id.
     * @return list<array<string, mixed>>
     * @see https://api.baselinker.com/index.php?method=getInventoryTransferSeries
     */
    public function getInventoryTransferSeries(array $filters = []): array
    {
        $response = $this->client->call('getInventoryTransferSeries', $filters);

        return $this->items($response, 'series');
    }

    /**
     * Retrieves a list of inventory transfers from BaseLinker storage.
     *
     * @param array<string, mixed> $filters Filters: filter_transfer_id, filter_warehouse_id, filter_target_warehouse_id, filter_transfer_status, filter_transfer_type, filter_series_id, filter_date_from, filter_date_to, filter_transfer_number, page.
     * @return list<array<string, mixed>>
     * @see https://api.baselinker.com/index.php?method=getInventoryTransfers
     */
    public function getInventoryTransfers(array $filters = []): array
    {
        $response = $this->client->call('getInventoryTransfers', $filters);

        return $this->items($response, 'transfers');
    }

    /**
     * Changes the status of an inventory transfer.
     *
     * @param int $transferId Transfer identifier
     * @param int $status Target transfer status: 0 - draft, 1 - commissioned, 2 - in progress, 3 - completed
     * @param array<string, mixed> $options Optional fields: completed_items.
     * @see https://api.baselinker.com/index.php?method=setInventoryTransferStatus
     */
    public function setInventoryTransferStatus(int $transferId, int $status, array $options = []): void
    {
        $this->client->call('setInventoryTransferStatus', ['transfer_id' => $transferId, 'status' => $status] + $options);
    }
}
