<?php

declare(strict_types=1);

namespace Beziworld\BaseLinker\Resources;

use Beziworld\BaseLinker\Data\File;

/**
 * Fulfillment deliveries.
 *
 * Every method maps one-to-one to the BaseLinker API method of the same name.
 */
final class Fulfillment extends Resource
{
    /**
     * Creates a new fulfillment delivery in draft status.
     *
     * @param int $warehouseId Source warehouse identifier
     * @param int $fulfillmentWarehouseId Fulfillment warehouse identifier
     * @param string $name Delivery name
     * @return array<string, mixed>
     * @see https://api.baselinker.com/index.php?method=addInventoryFulfillmentDelivery
     */
    public function addInventoryFulfillmentDelivery(int $warehouseId, int $fulfillmentWarehouseId, string $name): array
    {
        return $this->client->call('addInventoryFulfillmentDelivery', ['warehouse_id' => $warehouseId, 'fulfillment_warehouse_id' => $fulfillmentWarehouseId, 'name' => $name]);
    }

    /**
     * Adds products to a fulfillment delivery.
     *
     * @param int $deliveryId Fulfillment delivery identifier
     * @param array<array-key, mixed> $items API field "items". Array of items to add to the delivery
     * @return list<array<string, mixed>>
     * @see https://api.baselinker.com/index.php?method=addInventoryFulfillmentDeliveryItems
     */
    public function addInventoryFulfillmentDeliveryItems(int $deliveryId, array $items): array
    {
        $response = $this->client->call('addInventoryFulfillmentDeliveryItems', ['delivery_id' => $deliveryId, 'items' => $items]);

        return $this->items($response, 'items');
    }

    /**
     * Retrieves a list of fulfillment deliveries with optional filtering.
     *
     * @param array<string, mixed> $filters Filters: fulfillment_warehouse_id, fulfillment_warehouse_ids, external_warehouse_id, external_warehouse_ids, warehouse_id, status, statuses, date_from, date_to, filter_document_number, page.
     * @return list<array<string, mixed>>
     * @see https://api.baselinker.com/index.php?method=getInventoryFulfillmentDeliveries
     */
    public function getInventoryFulfillmentDeliveries(array $filters = []): array
    {
        $response = $this->client->call('getInventoryFulfillmentDeliveries', $filters);

        return $this->items($response, 'deliveries');
    }

    /**
     * Retrieves items from a specific fulfillment delivery.
     *
     * @param int $deliveryId Fulfillment delivery identifier
     * @param array<string, mixed> $options Optional fields: page.
     * @return list<array<string, mixed>>
     * @see https://api.baselinker.com/index.php?method=getInventoryFulfillmentDeliveryItems
     */
    public function getInventoryFulfillmentDeliveryItems(int $deliveryId, array $options = []): array
    {
        $response = $this->client->call('getInventoryFulfillmentDeliveryItems', ['delivery_id' => $deliveryId] + $options);

        return $this->items($response, 'items');
    }

    /**
     * Downloads labels of a selected type for a specific fulfillment delivery.
     *
     * @param int $deliveryId Fulfillment delivery identifier
     * @param string $type Type of labels to download
     * @return list<File>
     * @see https://api.baselinker.com/index.php?method=getInventoryFulfillmentDeliveryLabels
     */
    public function getInventoryFulfillmentDeliveryLabels(int $deliveryId, string $type): array
    {
        $response = $this->client->call('getInventoryFulfillmentDeliveryLabels', ['delivery_id' => $deliveryId, 'type' => $type]);

        return array_map(static fn(array $row): File => File::fromResponse($row, 'label'), $this->items($response, 'labels'));
    }

    /**
     * Submits (registers) a fulfillment delivery in draft or packing status.
     *
     * @param int $deliveryId Fulfillment delivery identifier to submit
     * @param array<string, mixed> $options Optional fields: date_arrival.
     * @return array<string, mixed>
     * @see https://api.baselinker.com/index.php?method=runInventoryFulfillmentDeliverySubmission
     */
    public function runInventoryFulfillmentDeliverySubmission(int $deliveryId, array $options = []): array
    {
        return $this->client->call('runInventoryFulfillmentDeliverySubmission', ['delivery_id' => $deliveryId] + $options);
    }
}
