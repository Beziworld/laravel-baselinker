<?php

declare(strict_types=1);

namespace Beziworld\BaseLinker\Resources;

use Beziworld\BaseLinker\Data\JournalEntry;
use Beziworld\BaseLinker\Data\Order;
use Beziworld\BaseLinker\Data\OrderSource;
use Beziworld\BaseLinker\Data\OrderStatus;
use DateTimeInterface;
use Illuminate\Support\LazyCollection;

/**
 * Orders: lists, fields, products, payments, statuses, journal, sources and macros.
 *
 * Every method maps one-to-one to the BaseLinker API method of the same name.
 */
final class Orders extends Resource
{
    /**
     * Adds a new order to the order manager.
     *
     * @param array<string, mixed> $order Fields: order_status_id, custom_source_id, date_add, currency, payment_method, payment_method_cod, paid, user_comments, admin_comments, email, phone, user_login, delivery_method, delivery_price, delivery_fullname, delivery_company, delivery_address, delivery_postcode, delivery_city, delivery_state, delivery_country_code, delivery_point_id, delivery_point_name, delivery_point_address, delivery_point_postcode, delivery_point_city, invoice_fullname, invoice_company, invoice_nip, invoice_address, invoice_postcode, invoice_city, invoice_state, invoice_country_code, want_invoice, extra_field_1, extra_field_2, custom_extra_fields, products.
     * @see https://api.baselinker.com/index.php?method=addOrder
     */
    public function addOrder(array $order): ?int
    {
        $response = $this->client->call('addOrder', $order);

        return $this->int($response, 'order_id');
    }

    /**
     * Creates a new order by splitting selected products from an existing order.
     *
     * @param int $orderId ID of the order to split
     * @param array<array-key, mixed> $itemsToSplit API field "items_to_split". A list of products to move to the new order
     * @param array<string, mixed> $options Optional fields: delivery_cost_to_split.
     * @see https://api.baselinker.com/index.php?method=addOrderBySplit
     */
    public function addOrderBySplit(int $orderId, array $itemsToSplit, array $options = []): ?int
    {
        $response = $this->client->call('addOrderBySplit', ['order_id' => $orderId, 'items_to_split' => $itemsToSplit] + $options);

        return $this->int($response, 'new_order_id');
    }

    /**
     * Adds a new order to the Base order manager by duplicating an existing order.
     *
     * @param int $orderId ID of the order to duplicate
     * @see https://api.baselinker.com/index.php?method=addOrderDuplicate
     */
    public function addOrderDuplicate(int $orderId): ?int
    {
        $response = $this->client->call('addOrderDuplicate', ['order_id' => $orderId]);

        return $this->int($response, 'order_id');
    }

    /**
     * Deletes multiple orders from the Base order manager.
     *
     * @param array<array-key, mixed> $orderIds API field "order_ids". Array of order identifiers from Base order manager (max 1000 at a time)
     * @return list<int>
     * @see https://api.baselinker.com/index.php?method=deleteOrders
     */
    public function deleteOrders(array $orderIds): array
    {
        $response = $this->client->call('deleteOrders', ['order_ids' => $orderIds]);

        return $this->ints($response, 'deleted_order_ids');
    }

    /**
     * Downloads orders from a specific date from the Base order manager.
     *
     * @param array<string, mixed> $filters Filters: order_id, date_confirmed_from, date_from, id_from, get_unconfirmed_orders, status_id, filter_email, filter_order_source, filter_order_source_id, filter_shop_order_id, filter_external_order_id, include_custom_extra_fields, include_commissions, include_connect_data, include_discounts_data.
     * @return list<Order>
     * @see https://api.baselinker.com/index.php?method=getOrders
     */
    public function getOrders(array $filters = []): array
    {
        $response = $this->client->call('getOrders', $filters);

        return array_map(Order::fromArray(...), $this->items($response, 'orders'));
    }

    /**
     * Edits selected fields (for example addresses or notes) of an order; only the given fields are changed.
     *
     * @param int $orderId Order identifier from the Base order manager
     * @param array<string, mixed> $fields Fields to change: currency, admin_comments, user_comments, payment_method, payment_method_cod, email, phone, user_login, delivery_method, delivery_price, delivery_fullname, delivery_company, delivery_address, delivery_postcode, delivery_city, delivery_state, delivery_country_code, delivery_point_id, delivery_point_name, delivery_point_address, delivery_point_postcode, delivery_point_city, invoice_fullname, invoice_company, invoice_nip, invoice_address, invoice_postcode, invoice_city, invoice_state, invoice_country_code, want_invoice, extra_field_1, extra_field_2, custom_extra_fields, pick_state, pack_state, star.
     * @see https://api.baselinker.com/index.php?method=setOrderFields
     */
    public function setOrderFields(int $orderId, array $fields = []): void
    {
        $this->client->call('setOrderFields', ['order_id' => $orderId] + $fields);
    }

    /**
     * Merges multiple orders into one, based on the selected merge mode.
     *
     * @param int $mainOrderId ID of the main order (its shipping and invoice data will be retained)
     * @param array<array-key, mixed> $orderIdsToMerge API field "order_ids_to_merge". List of other order IDs to merge
     * @param string $mergeMode Merge mode: technical_merge (creates a new technical order without changing the originals) or into_main_order (moves items into the main order and...
     * @param array<string, mixed> $options Optional fields: sum_delivery_costs.
     * @see https://api.baselinker.com/index.php?method=setOrdersMerge
     */
    public function setOrdersMerge(int $mainOrderId, array $orderIdsToMerge, string $mergeMode, array $options = []): ?int
    {
        $response = $this->client->call('setOrdersMerge', ['main_order_id' => $mainOrderId, 'order_ids_to_merge' => $orderIdsToMerge, 'merge_mode' => $mergeMode] + $options);

        return $this->int($response, 'merged_order_id');
    }

    /**
     * Adds a new product to your order.
     *
     * @param int $orderId Order Identifier from Base order manager
     * @param array<string, mixed> $product Fields: storage, storage_id, product_id, variant_id, auction_id, name, sku, ean, location, warehouse_id, attributes, price_brutto, tax_rate, quantity, weight.
     * @see https://api.baselinker.com/index.php?method=addOrderProduct
     */
    public function addOrderProduct(int $orderId, array $product = []): ?int
    {
        $response = $this->client->call('addOrderProduct', ['order_id' => $orderId] + $product);

        return $this->int($response, 'order_product_id');
    }

    /**
     * Removes a specific product from the order.
     *
     * @param int $orderId Order ID from Base order manager
     * @param int $orderProductId Order item ID from Base order manager
     * @see https://api.baselinker.com/index.php?method=deleteOrderProduct
     */
    public function deleteOrderProduct(int $orderId, int $orderProductId): void
    {
        $this->client->call('deleteOrderProduct', ['order_id' => $orderId, 'order_product_id' => $orderProductId]);
    }

    /**
     * Edits selected fields (for example price or quantity) of an order product; only the given fields are changed.
     *
     * @param int $orderId Order identifier from the Base order manager
     * @param int $orderProductId Order item ID from Base order manager
     * @param array<string, mixed> $fields Fields to change: storage, storage_id, product_id, variant_id, auction_id, name, sku, ean, location, warehouse_id, attributes, price_brutto, tax_rate, quantity, weight.
     * @see https://api.baselinker.com/index.php?method=setOrderProductFields
     */
    public function setOrderProductFields(int $orderId, int $orderProductId, array $fields = []): void
    {
        $this->client->call('setOrderProductFields', ['order_id' => $orderId, 'order_product_id' => $orderProductId] + $fields);
    }

    /**
     * Retrieves payment history for a selected order, including an external payment identifier from the payment gateway.
     *
     * @param int $orderId Order Identifier from Base order manager
     * @param array<string, mixed> $options Optional fields: show_full_history.
     * @return list<array<string, mixed>>
     * @see https://api.baselinker.com/index.php?method=getOrderPaymentsHistory
     */
    public function getOrderPaymentsHistory(int $orderId, array $options = []): array
    {
        $response = $this->client->call('getOrderPaymentsHistory', ['order_id' => $orderId] + $options);

        return $this->items($response, 'payments');
    }

    /**
     * Adds a payment to the order.
     *
     * @param int $orderId Order ID number
     * @param float $paymentDone The amount of the payment
     * @param int $paymentDate Payment date unixtime
     * @param array<string, mixed> $options Optional fields: payment_comment, external_payment_id.
     * @see https://api.baselinker.com/index.php?method=setOrderPayment
     */
    public function setOrderPayment(int $orderId, float $paymentDone, int $paymentDate, array $options = []): void
    {
        $this->client->call('setOrderPayment', ['order_id' => $orderId, 'payment_done' => $paymentDone, 'payment_date' => $paymentDate] + $options);
    }

    /**
     * Creates a new order status or updates an existing one if status_id is provided.
     *
     * @param array<string, mixed> $status Fields: status_id, name, short_name, name_for_customer, color, type, group_id, comments.
     * @see https://api.baselinker.com/index.php?method=addOrderStatus
     */
    public function addOrderStatus(array $status): ?int
    {
        $response = $this->client->call('addOrderStatus', $status);

        return $this->int($response, 'status_id');
    }

    /**
     * Creates a new order status group or updates an existing one if group_id is provided.
     *
     * @param array<string, mixed> $group Fields: group_id, name.
     * @see https://api.baselinker.com/index.php?method=addOrderStatusGroup
     */
    public function addOrderStatusGroup(array $group): ?int
    {
        $response = $this->client->call('addOrderStatusGroup', $group);

        return $this->int($response, 'group_id');
    }

    /**
     * Deletes an order status.
     *
     * @param int $statusId ID of the status to delete
     * @param int $targetStatusId ID of the status to which items with the deleted status will be migrated
     * @see https://api.baselinker.com/index.php?method=deleteOrderStatus
     */
    public function deleteOrderStatus(int $statusId, int $targetStatusId): void
    {
        $this->client->call('deleteOrderStatus', ['status_id' => $statusId, 'target_status_id' => $targetStatusId]);
    }

    /**
     * Deletes an order status group.
     *
     * @param int $groupId ID of the status group to delete
     * @see https://api.baselinker.com/index.php?method=deleteOrderStatusGroup
     */
    public function deleteOrderStatusGroup(int $groupId): void
    {
        $this->client->call('deleteOrderStatusGroup', ['group_id' => $groupId]);
    }

    /**
     * Returns the list of order status groups.
     *
     * @return list<array<string, mixed>>
     * @see https://api.baselinker.com/index.php?method=getOrderStatusGroups
     */
    public function getOrderStatusGroups(): array
    {
        $response = $this->client->call('getOrderStatusGroups');

        return $this->items($response, 'status_groups');
    }

    /**
     * Returns order statuses created by the user in the order manager.
     *
     * @return list<OrderStatus>
     * @see https://api.baselinker.com/index.php?method=getOrderStatusList
     */
    public function getOrderStatusList(): array
    {
        $response = $this->client->call('getOrderStatusList');

        return array_map(OrderStatus::fromArray(...), $this->items($response, 'statuses'));
    }

    /**
     * Changes order status.
     *
     * @param int $orderId Order ID number
     * @param int $statusId Status ID number
     * @see https://api.baselinker.com/index.php?method=setOrderStatus
     */
    public function setOrderStatus(int $orderId, int $statusId): void
    {
        $this->client->call('setOrderStatus', ['order_id' => $orderId, 'status_id' => $statusId]);
    }

    /**
     * Sets the status of many orders at once.
     *
     * @param array<array-key, mixed> $orderIds API field "order_ids". Array of Order ID numbers
     * @param int $statusId Status ID number
     * @see https://api.baselinker.com/index.php?method=setOrderStatuses
     */
    public function setOrderStatuses(array $orderIds, int $statusId): void
    {
        $this->client->call('setOrderStatuses', ['order_ids' => $orderIds, 'status_id' => $statusId]);
    }

    /**
     * Downloads a list of order events from the last 3 days.
     *
     * @param array<string, mixed> $filters Filters: last_log_id, logs_types, order_id.
     * @return list<JournalEntry>
     * @see https://api.baselinker.com/index.php?method=getJournalList
     */
    public function getJournalList(array $filters = []): array
    {
        $response = $this->client->call('getJournalList', $filters);

        return array_map(JournalEntry::fromArray(...), $this->items($response, 'logs'));
    }

    /**
     * Returns extra fields defined for the orders.
     *
     * @return list<array<string, mixed>>
     * @see https://api.baselinker.com/index.php?method=getOrderExtraFields
     */
    public function getOrderExtraFields(): array
    {
        $response = $this->client->call('getOrderExtraFields');

        return $this->items($response, 'extra_fields');
    }

    /**
     * Retrieves pick pack history for a selected order.
     *
     * @param int $orderId Order Identifier from Base order manager
     * @param array<string, mixed> $options Optional fields: action_type.
     * @return list<array<string, mixed>>
     * @see https://api.baselinker.com/index.php?method=getOrderPickPackHistory
     */
    public function getOrderPickPackHistory(int $orderId, array $options = []): array
    {
        $response = $this->client->call('getOrderPickPackHistory', ['order_id' => $orderId] + $options);

        return $this->items($response, 'history');
    }

    /**
     * Returns a list of all configured printout templates available for orders.
     *
     * @return list<array<string, mixed>>
     * @see https://api.baselinker.com/index.php?method=getOrderPrintoutTemplates
     */
    public function getOrderPrintoutTemplates(): array
    {
        $response = $this->client->call('getOrderPrintoutTemplates');

        return $this->items($response, 'printouts');
    }

    /**
     * Returns types of order sources along with their IDs.
     *
     * @return list<OrderSource>
     * @see https://api.baselinker.com/index.php?method=getOrderSources
     */
    public function getOrderSources(): array
    {
        $response = $this->client->call('getOrderSources');

        return OrderSource::listFromSources($this->map($response, 'sources'));
    }

    /**
     * Retrieves transaction details for a selected order.
     *
     * @param int $orderId Order Identifier from Base order manager
     * @param array<string, mixed> $options Optional fields: include_complex_taxes, include_amazon_data.
     * @return array<string, mixed>
     * @see https://api.baselinker.com/index.php?method=getOrderTransactionData
     */
    public function getOrderTransactionData(int $orderId, array $options = []): array
    {
        return $this->client->call('getOrderTransactionData', ['order_id' => $orderId] + $options);
    }

    /**
     * Searches for orders related to the given e-mail address.
     *
     * @param string $email The e-mail address we search for in orders
     * @return list<array<string, mixed>>
     * @see https://api.baselinker.com/index.php?method=getOrdersByEmail
     */
    public function getOrdersByEmail(string $email): array
    {
        $response = $this->client->call('getOrdersByEmail', ['email' => $email]);

        return $this->items($response, 'orders');
    }

    /**
     * Searches for orders related to the given phone number.
     *
     * @param string $phone The phone number we search for in orders
     * @return list<array<string, mixed>>
     * @see https://api.baselinker.com/index.php?method=getOrdersByPhone
     */
    public function getOrdersByPhone(string $phone): array
    {
        $response = $this->client->call('getOrdersByPhone', ['phone' => $phone]);

        return $this->items($response, 'orders');
    }

    /**
     * Runs personal trigger for orders automatic actions.
     *
     * @param int $orderId Order identifier from Base order manager
     * @param int $triggerId Identifier of personal trigger from orders automatic actions
     * @see https://api.baselinker.com/index.php?method=runOrderMacroTrigger
     */
    public function runOrderMacroTrigger(int $orderId, int $triggerId): void
    {
        $this->client->call('runOrderMacroTrigger', ['order_id' => $orderId, 'trigger_id' => $triggerId]);
    }

    /**
     * Maximum number of orders returned by one getOrders call.
     */
    public const int PAGE_SIZE = 100;

    /**
     * Single order by id (confirmed or not), or null when it does not exist.
     *
     * @param array<string, mixed> $filters Additional getOrders flags, for example include_custom_extra_fields.
     */
    public function find(int $orderId, array $filters = []): ?Order
    {
        return $this->getOrders(['order_id' => $orderId, 'get_unconfirmed_orders' => true] + $filters)[0] ?? null;
    }

    /**
     * Lazily iterates confirmed orders, following the confirmation date as recommended by BaseLinker.
     *
     * Every page starts at the date_confirmed of the last order of the
     * previous page plus one second; iteration stops at a page with fewer
     * than 100 orders. Unconfirmed orders have no confirmation date and are
     * excluded; use iterateById() to include them.
     *
     * @param array<string, mixed> $filters Other getOrders filters, for example status_id or filter_order_source.
     * @return LazyCollection<int, Order>
     */
    public function iterate(DateTimeInterface|int $confirmedFrom, array $filters = []): LazyCollection
    {
        $from = $confirmedFrom instanceof DateTimeInterface ? $confirmedFrom->getTimestamp() : $confirmedFrom;

        return LazyCollection::make(function () use ($from, $filters) {
            $cursor = $from;

            do {
                $orders = $this->getOrders(['date_confirmed_from' => $cursor, 'get_unconfirmed_orders' => false] + $filters);

                foreach ($orders as $order) {
                    yield $order;
                }

                $last = $orders !== [] ? $orders[array_key_last($orders)]->dateConfirmed : null;

                if ($last === null) {
                    return;
                }

                $cursor = max($cursor, $last->getTimestamp()) + 1;
            } while (count($orders) >= self::PAGE_SIZE);
        });
    }

    /**
     * Lazily iterates orders by ascending id, starting at the given order id.
     *
     * Every page starts at the id of the last order of the previous page plus
     * one; iteration stops at a page with fewer than 100 orders.
     *
     * @param array<string, mixed> $filters Other getOrders filters, for example get_unconfirmed_orders or status_id.
     * @return LazyCollection<int, Order>
     */
    public function iterateById(int $idFrom = 1, array $filters = []): LazyCollection
    {
        return LazyCollection::make(function () use ($idFrom, $filters) {
            $cursor = $idFrom;

            do {
                $orders = $this->getOrders(['id_from' => $cursor] + $filters);

                foreach ($orders as $order) {
                    yield $order;
                }

                if ($orders === []) {
                    return;
                }

                $cursor = max($cursor, $orders[array_key_last($orders)]->id) + 1;
            } while (count($orders) >= self::PAGE_SIZE);
        });
    }
}
