<?php

declare(strict_types=1);

namespace Beziworld\BaseLinker\Resources;

/**
 * Stocktakes (inventory counts).
 *
 * Every method maps one-to-one to the BaseLinker API method of the same name.
 */
final class Stocktakes extends Resource
{
    /**
     * Creates a new stocktake (inventory count) in draft status.
     *
     * @param int $warehouseId Warehouse identifier
     * @param int $scope Stocktake scope defining which products are included: 0 - all products, 1 - manual product selection, 3 - specific warehouse zones, 4 - specific...
     * @param array<string, mixed> $options Optional fields: name, priority, assigned_to, date_scheduled, date_due, approval_method, blind_count, confirm_location, allow_add_remove_items, product_ids, scope_zones, scope_locations_prefix, scope_random_count.
     * @return array<string, mixed>
     * @see https://api.baselinker.com/index.php?method=addInventoryStocktake
     */
    public function addInventoryStocktake(int $warehouseId, int $scope, array $options = []): array
    {
        return $this->client->call('addInventoryStocktake', ['warehouse_id' => $warehouseId, 'scope' => $scope] + $options);
    }

    /**
     * Adds items to an existing stocktake in draft status, or updates the quantity of an existing item by item_id.
     *
     * @param int $stocktakeId Stocktake identifier
     * @param array<array-key, mixed> $items API field "items". List of items to add or update
     * @return list<array<string, mixed>>
     * @see https://api.baselinker.com/index.php?method=addInventoryStocktakeItems
     */
    public function addInventoryStocktakeItems(int $stocktakeId, array $items): array
    {
        $response = $this->client->call('addInventoryStocktakeItems', ['stocktake_id' => $stocktakeId, 'items' => $items]);

        return $this->items($response, 'items');
    }

    /**
     * Retrieves items from a specific stocktake.
     *
     * @param int $stocktakeId Stocktake identifier
     * @param array<string, mixed> $options Optional fields: page.
     * @return list<array<string, mixed>>
     * @see https://api.baselinker.com/index.php?method=getInventoryStocktakeItems
     */
    public function getInventoryStocktakeItems(int $stocktakeId, array $options = []): array
    {
        $response = $this->client->call('getInventoryStocktakeItems', ['stocktake_id' => $stocktakeId] + $options);

        return $this->items($response, 'items');
    }

    /**
     * Retrieves a list of available stocktake numbering series.
     *
     * @param array<string, mixed> $filters Filters: warehouse_id.
     * @return list<array<string, mixed>>
     * @see https://api.baselinker.com/index.php?method=getInventoryStocktakeSeries
     */
    public function getInventoryStocktakeSeries(array $filters = []): array
    {
        $response = $this->client->call('getInventoryStocktakeSeries', $filters);

        return $this->items($response, 'series');
    }

    /**
     * Retrieves a paginated list of stocktakes from BaseLinker storage.
     *
     * @param array<string, mixed> $filters Filters: filter_stocktake_id, filter_warehouse_id, filter_stocktake_status, filter_series_id, filter_stocktake_number, filter_date_from, filter_date_to, page.
     * @return list<array<string, mixed>>
     * @see https://api.baselinker.com/index.php?method=getInventoryStocktakes
     */
    public function getInventoryStocktakes(array $filters = []): array
    {
        $response = $this->client->call('getInventoryStocktakes', $filters);

        return $this->items($response, 'stocktakes');
    }

    /**
     * Changes the status of a stocktake.
     *
     * @param int $stocktakeId Stocktake identifier
     * @param int $status Target stocktake status: 0 - draft, 1 - commissioned, 2 - in progress, 3 - completed, 4 - counted, 5 - rejected
     * @param array<string, mixed> $options Optional fields: completed_items.
     * @see https://api.baselinker.com/index.php?method=setInventoryStocktakeStatus
     */
    public function setInventoryStocktakeStatus(int $stocktakeId, int $status, array $options = []): void
    {
        $this->client->call('setInventoryStocktakeStatus', ['stocktake_id' => $stocktakeId, 'status' => $status] + $options);
    }
}
