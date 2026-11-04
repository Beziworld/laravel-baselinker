<?php

declare(strict_types=1);

namespace Beziworld\BaseLinker\Resources;

use Beziworld\BaseLinker\Data\File;

/**
 * Purchase orders, suppliers and payers.
 *
 * Every method maps one-to-one to the BaseLinker API method of the same name.
 */
final class PurchaseOrders extends Resource
{
    /**
     * Creates a new purchase order in Base storage.
     *
     * @param array<string, mixed> $purchaseOrder Fields: warehouse_id, supplier_id, payer_id, currency, name, notes, invoice_no, date_cost_invoice_issue, date_delivery_expected, additional_costs, packages.
     * @return array<string, mixed>
     * @see https://api.baselinker.com/index.php?method=addInventoryPurchaseOrder
     */
    public function addInventoryPurchaseOrder(array $purchaseOrder): array
    {
        return $this->client->call('addInventoryPurchaseOrder', $purchaseOrder);
    }

    /**
     * Adds a cost invoice PDF file to a purchase order.
     *
     * @param int $purchaseOrderId Purchase order identifier from BaseLinker
     * @param string $contents Binary file contents; encoded as the API expects.
     * @param array<string, mixed> $options Optional fields: cost_invoice_no.
     * @see https://api.baselinker.com/index.php?method=addInventoryPurchaseOrderFile
     */
    public function addInventoryPurchaseOrderFile(int $purchaseOrderId, string $contents, array $options = []): void
    {
        $this->client->call('addInventoryPurchaseOrderFile', ['purchase_order_id' => $purchaseOrderId, 'file' => $this->encodeFile($contents)] + $options);
    }

    /**
     * Adds items to an existing purchase order, or updates an existing item while the purchase order is in draft status.
     *
     * @param int $orderId Purchase order identifier
     * @param array<array-key, mixed> $items API field "items". List of items to add or update
     * @return list<array<string, mixed>>
     * @see https://api.baselinker.com/index.php?method=addInventoryPurchaseOrderItems
     */
    public function addInventoryPurchaseOrderItems(int $orderId, array $items): array
    {
        $response = $this->client->call('addInventoryPurchaseOrderItems', ['order_id' => $orderId, 'items' => $items]);

        return $this->items($response, 'items');
    }

    /**
     * Removes selected items from an existing purchase order.
     *
     * @param int $orderId Purchase order identifier
     * @param array<array-key, mixed> $itemIds API field "item_ids". Purchase order item identifiers to remove
     * @return list<int>
     * @see https://api.baselinker.com/index.php?method=deleteInventoryPurchaseOrderItems
     */
    public function deleteInventoryPurchaseOrderItems(int $orderId, array $itemIds): array
    {
        $response = $this->client->call('deleteInventoryPurchaseOrderItems', ['order_id' => $orderId, 'item_ids' => $itemIds]);

        return $this->ints($response, 'deleted_item_ids');
    }

    /**
     * Downloads a cost invoice PDF file attached to a purchase order.
     *
     * @param int $purchaseOrderId Purchase order identifier from BaseLinker
     * @see https://api.baselinker.com/index.php?method=getInventoryPurchaseOrderFile
     */
    public function getInventoryPurchaseOrderFile(int $purchaseOrderId): File
    {
        $response = $this->client->call('getInventoryPurchaseOrderFile', ['purchase_order_id' => $purchaseOrderId]);

        return File::fromResponse($response, 'file', 'cost_invoice_no', 'file_name');
    }

    /**
     * Retrieves items from a specific purchase order.
     *
     * @param int $orderId Purchase order identifier
     * @param array<string, mixed> $options Optional fields: page.
     * @return list<array<string, mixed>>
     * @see https://api.baselinker.com/index.php?method=getInventoryPurchaseOrderItems
     */
    public function getInventoryPurchaseOrderItems(int $orderId, array $options = []): array
    {
        $response = $this->client->call('getInventoryPurchaseOrderItems', ['order_id' => $orderId] + $options);

        return $this->items($response, 'items');
    }

    /**
     * Returns a list of log entries for a given purchase order, allowing to track status changes, item modifications, and other events.
     *
     * @param int $purchaseOrderId Purchase order (delivery) identifier
     * @param array<string, mixed> $filters Filters: date_from, date_to, log_type, sort, page.
     * @return list<array<string, mixed>>
     * @see https://api.baselinker.com/index.php?method=getInventoryPurchaseOrderLogs
     */
    public function getInventoryPurchaseOrderLogs(int $purchaseOrderId, array $filters = []): array
    {
        $response = $this->client->call('getInventoryPurchaseOrderLogs', ['purchase_order_id' => $purchaseOrderId] + $filters);

        return $this->items($response, 'logs');
    }

    /**
     * Retrieves a list of available purchase order numbering series.
     *
     * @param array<string, mixed> $filters Filters: warehouse_id.
     * @return list<array<string, mixed>>
     * @see https://api.baselinker.com/index.php?method=getInventoryPurchaseOrderSeries
     */
    public function getInventoryPurchaseOrderSeries(array $filters = []): array
    {
        $response = $this->client->call('getInventoryPurchaseOrderSeries', $filters);

        return $this->items($response, 'series');
    }

    /**
     * Retrieves a list of purchase orders from Base storage.
     *
     * @param array<string, mixed> $filters Filters: warehouse_id, supplier_id, series_id, date_from, date_to, filter_document_number, filter_product_id, page.
     * @return list<array<string, mixed>>
     * @see https://api.baselinker.com/index.php?method=getInventoryPurchaseOrders
     */
    public function getInventoryPurchaseOrders(array $filters = []): array
    {
        $response = $this->client->call('getInventoryPurchaseOrders', $filters);

        return $this->items($response, 'purchase_orders');
    }

    /**
     * Changes the status of a purchase order.
     *
     * @param int $orderId Purchase order identifier
     * @param int $status New order status
     * @param array<string, mixed> $options Optional fields: completed_items.
     * @see https://api.baselinker.com/index.php?method=setInventoryPurchaseOrderStatus
     */
    public function setInventoryPurchaseOrderStatus(int $orderId, int $status, array $options = []): void
    {
        $this->client->call('setInventoryPurchaseOrderStatus', ['order_id' => $orderId, 'status' => $status] + $options);
    }

    /**
     * Adds a new supplier or updates an existing one in BaseLinker storage.
     *
     * @param array<string, mixed> $supplier Fields: supplier_id, name, take_product_cost_from, take_product_code_from, address, postcode, city, phone, email, email_copy_to, currency, tax_no.
     * @see https://api.baselinker.com/index.php?method=addInventorySupplier
     */
    public function addInventorySupplier(array $supplier): ?int
    {
        $response = $this->client->call('addInventorySupplier', $supplier);

        return $this->int($response, 'supplier_id');
    }

    /**
     * Removes a supplier from BaseLinker storage.
     *
     * @param int $supplierId Supplier identifier
     * @see https://api.baselinker.com/index.php?method=deleteInventorySupplier
     */
    public function deleteInventorySupplier(int $supplierId): void
    {
        $this->client->call('deleteInventorySupplier', ['supplier_id' => $supplierId]);
    }

    /**
     * Retrieves a list of suppliers available in BaseLinker storage.
     *
     * @param array<string, mixed> $filters Filters: filter_id, filter_name.
     * @return list<array<string, mixed>>
     * @see https://api.baselinker.com/index.php?method=getInventorySuppliers
     */
    public function getInventorySuppliers(array $filters = []): array
    {
        $response = $this->client->call('getInventorySuppliers', $filters);

        return $this->items($response, 'suppliers');
    }

    /**
     * Adds a new payer or updates an existing one in BaseLinker storage.
     *
     * @param array<string, mixed> $payer Fields: payer_id, name, address, postcode, city, tax_no.
     * @see https://api.baselinker.com/index.php?method=addInventoryPayer
     */
    public function addInventoryPayer(array $payer): ?int
    {
        $response = $this->client->call('addInventoryPayer', $payer);

        return $this->int($response, 'payer_id');
    }

    /**
     * Removes a payer from BaseLinker storage.
     *
     * @param int $payerId Payer identifier
     * @see https://api.baselinker.com/index.php?method=deleteInventoryPayer
     */
    public function deleteInventoryPayer(int $payerId): void
    {
        $this->client->call('deleteInventoryPayer', ['payer_id' => $payerId]);
    }

    /**
     * Retrieves a list of payers available in BaseLinker storage.
     *
     * @param array<string, mixed> $filters Filters: filter_id, filter_name.
     * @return list<array<string, mixed>>
     * @see https://api.baselinker.com/index.php?method=getInventoryPayers
     */
    public function getInventoryPayers(array $filters = []): array
    {
        $response = $this->client->call('getInventoryPayers', $filters);

        return $this->items($response, 'payers');
    }
}
