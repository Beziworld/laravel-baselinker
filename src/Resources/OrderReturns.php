<?php

declare(strict_types=1);

namespace Beziworld\BaseLinker\Resources;

use Beziworld\BaseLinker\Data\OrderReturn;
use Beziworld\BaseLinker\Data\OrderStatus;
use Beziworld\BaseLinker\Data\ReturnJournalEntry;
use Illuminate\Support\LazyCollection;

/**
 * Order returns: lists, fields, products, statuses, refunds and the return journal.
 *
 * Every method maps one-to-one to the BaseLinker API method of the same name.
 */
final class OrderReturns extends Resource
{
    /**
     * Adds a new order return.
     *
     * @param array<string, mixed> $orderReturn Fields: order_id, status_id, custom_source_id, reference_number, date_add, currency, refunded, admin_comments, email, phone, user_login, delivery_price, delivery_fullname, delivery_company, delivery_address, delivery_postcode, delivery_city, delivery_state, delivery_country_code, extra_field_1, extra_field_2, custom_extra_fields, products, refund_account_number, refund_iban, refund_swift.
     * @see https://api.baselinker.com/index.php?method=addOrderReturn
     */
    public function addOrderReturn(array $orderReturn): ?int
    {
        $response = $this->client->call('addOrderReturn', $orderReturn);

        return $this->int($response, 'return_id');
    }

    /**
     * Returns extra fields defined for order returns.
     *
     * @return list<array<string, mixed>>
     * @see https://api.baselinker.com/index.php?method=getOrderReturnExtraFields
     */
    public function getOrderReturnExtraFields(): array
    {
        $response = $this->client->call('getOrderReturnExtraFields');

        return $this->items($response, 'extra_fields');
    }

    /**
     * Downloads order returns from a specific date from the Base return manager.
     *
     * @param array<string, mixed> $filters Filters: order_id, return_id, date_from, id_from, status_id, filter_order_return_source, filter_order_return_source_id, include_custom_extra_fields, include_connect_data.
     * @return list<OrderReturn>
     * @see https://api.baselinker.com/index.php?method=getOrderReturns
     */
    public function getOrderReturns(array $filters = []): array
    {
        $response = $this->client->call('getOrderReturns', $filters);

        return array_map(OrderReturn::fromArray(...), $this->items($response, 'returns'));
    }

    /**
     * Edits selected fields of a specific order return.
     *
     * @param int $returnId Order return identifier
     * @param array<string, mixed> $fields Fields to change: admin_comments, email, phone, user_login, delivery_price, delivery_fullname, delivery_company, delivery_address, delivery_postcode, delivery_city, delivery_state, delivery_country_code, extra_field_1, extra_field_2, custom_extra_fields.
     * @see https://api.baselinker.com/index.php?method=setOrderReturnFields
     */
    public function setOrderReturnFields(int $returnId, array $fields = []): void
    {
        $this->client->call('setOrderReturnFields', ['return_id' => $returnId] + $fields);
    }

    /**
     * Adds new product to existing order return.
     *
     * @param int $returnId Order return identifier
     * @param array<string, mixed> $product Fields: order_product_id, storage, storage_id, product_id, variant_id, auction_id, name, sku, ean, location, warehouse_id, attributes, price_brutto, tax_rate, quantity, weight, status_id, return_reason_id.
     * @see https://api.baselinker.com/index.php?method=addOrderReturnProduct
     */
    public function addOrderReturnProduct(int $returnId, array $product = []): ?int
    {
        $response = $this->client->call('addOrderReturnProduct', ['return_id' => $returnId] + $product);

        return $this->int($response, 'order_return_product_id');
    }

    /**
     * Removes a specific product from the return.
     *
     * @param int $returnId Order return ID
     * @param int $orderReturnProductId Order return item ID
     * @see https://api.baselinker.com/index.php?method=deleteOrderReturnProduct
     */
    public function deleteOrderReturnProduct(int $returnId, int $orderReturnProductId): void
    {
        $this->client->call('deleteOrderReturnProduct', ['return_id' => $returnId, 'order_return_product_id' => $orderReturnProductId]);
    }

    /**
     * Edits selected fields (for example price or quantity) of an order return product; only the given fields are changed.
     *
     * @param int $returnId Order return identifier from the Base order manager
     * @param int $orderReturnProductId Order return item ID from Base order manager
     * @param array<string, mixed> $fields Fields to change: storage, storage_id, product_id, variant_id, auction_id, name, sku, ean, location, warehouse_id, attributes, price_brutto, tax_rate, quantity, weight, status_id, return_reason_id.
     * @see https://api.baselinker.com/index.php?method=setOrderReturnProductFields
     */
    public function setOrderReturnProductFields(int $returnId, int $orderReturnProductId, array $fields = []): void
    {
        $this->client->call('setOrderReturnProductFields', ['return_id' => $returnId, 'order_return_product_id' => $orderReturnProductId] + $fields);
    }

    /**
     * Creates a new order return status or updates an existing one if status_id is provided.
     *
     * @param array<string, mixed> $status Fields: status_id, name, name_for_customer, color, group_id.
     * @see https://api.baselinker.com/index.php?method=addOrderReturnStatus
     */
    public function addOrderReturnStatus(array $status): ?int
    {
        $response = $this->client->call('addOrderReturnStatus', $status);

        return $this->int($response, 'status_id');
    }

    /**
     * Creates a new order return status group or updates an existing one if group_id is provided.
     *
     * @param array<string, mixed> $group Fields: group_id, name.
     * @see https://api.baselinker.com/index.php?method=addOrderReturnStatusGroup
     */
    public function addOrderReturnStatusGroup(array $group): ?int
    {
        $response = $this->client->call('addOrderReturnStatusGroup', $group);

        return $this->int($response, 'group_id');
    }

    /**
     * Deletes an order return status.
     *
     * @param int $statusId ID of the status to delete
     * @param int $targetStatusId ID of the status to which items with the deleted status will be migrated
     * @see https://api.baselinker.com/index.php?method=deleteOrderReturnStatus
     */
    public function deleteOrderReturnStatus(int $statusId, int $targetStatusId): void
    {
        $this->client->call('deleteOrderReturnStatus', ['status_id' => $statusId, 'target_status_id' => $targetStatusId]);
    }

    /**
     * Deletes an order return status group.
     *
     * @param int $groupId ID of the status group to delete
     * @see https://api.baselinker.com/index.php?method=deleteOrderReturnStatusGroup
     */
    public function deleteOrderReturnStatusGroup(int $groupId): void
    {
        $this->client->call('deleteOrderReturnStatusGroup', ['group_id' => $groupId]);
    }

    /**
     * Returns a list of order return item statuses.
     *
     * @return list<array<string, mixed>>
     * @see https://api.baselinker.com/index.php?method=getOrderReturnProductStatuses
     */
    public function getOrderReturnProductStatuses(): array
    {
        $response = $this->client->call('getOrderReturnProductStatuses');

        return $this->items($response, 'order_return_product_statuses');
    }

    /**
     * Returns a list of order return reasons.
     *
     * @return list<array<string, mixed>>
     * @see https://api.baselinker.com/index.php?method=getOrderReturnReasonsList
     */
    public function getOrderReturnReasonsList(): array
    {
        $response = $this->client->call('getOrderReturnReasonsList');

        return $this->items($response, 'return_reasons');
    }

    /**
     * Returns the list of order return status groups.
     *
     * @return list<array<string, mixed>>
     * @see https://api.baselinker.com/index.php?method=getOrderReturnStatusGroups
     */
    public function getOrderReturnStatusGroups(): array
    {
        $response = $this->client->call('getOrderReturnStatusGroups');

        return $this->items($response, 'status_groups');
    }

    /**
     * Returns order return statuses created by the user in the order manager.
     *
     * @return list<OrderStatus>
     * @see https://api.baselinker.com/index.php?method=getOrderReturnStatusList
     */
    public function getOrderReturnStatusList(): array
    {
        $response = $this->client->call('getOrderReturnStatusList');

        return array_map(OrderStatus::fromArray(...), $this->items($response, 'statuses'));
    }

    /**
     * Changes order return status.
     *
     * @param int $returnId Order return ID number
     * @param int $statusId Status ID number
     * @see https://api.baselinker.com/index.php?method=setOrderReturnStatus
     */
    public function setOrderReturnStatus(int $returnId, int $statusId): void
    {
        $this->client->call('setOrderReturnStatus', ['return_id' => $returnId, 'status_id' => $statusId]);
    }

    /**
     * Sets the status of many order returns at once.
     *
     * @param array<array-key, mixed> $returnIds API field "return_ids". Array of Order return ID numbers
     * @param int $statusId Order return status ID number
     * @see https://api.baselinker.com/index.php?method=setOrderReturnStatuses
     */
    public function setOrderReturnStatuses(array $returnIds, int $statusId): void
    {
        $this->client->call('setOrderReturnStatuses', ['return_ids' => $returnIds, 'status_id' => $statusId]);
    }

    /**
     * Retrieves payment history for a selected order, including an external payment identifier from the payment gateway.
     *
     * @param int $returnId Order return identifier
     * @param array<string, mixed> $options Optional fields: show_full_history.
     * @return list<array<string, mixed>>
     * @see https://api.baselinker.com/index.php?method=getOrderReturnPaymentsHistory
     */
    public function getOrderReturnPaymentsHistory(int $returnId, array $options = []): array
    {
        $response = $this->client->call('getOrderReturnPaymentsHistory', ['return_id' => $returnId] + $options);

        return $this->items($response, 'payments');
    }

    /**
     * Marks an order return as refunded.
     *
     * @param int $returnId Order return ID
     * @param float $orderRefundDone The amount of the refund
     * @param int $refundDate Refund date (unixtime format)
     * @param array<string, mixed> $options Optional fields: refund_comment, external_refund_id.
     * @see https://api.baselinker.com/index.php?method=setOrderReturnRefund
     */
    public function setOrderReturnRefund(int $returnId, float $orderRefundDone, int $refundDate, array $options = []): void
    {
        $this->client->call('setOrderReturnRefund', ['return_id' => $returnId, 'order_refund_done' => $orderRefundDone, 'refund_date' => $refundDate] + $options);
    }

    /**
     * Downloads a list of return events from the last 3 days.
     *
     * @param array<string, mixed> $filters Filters: last_log_id, logs_types, return_id.
     * @return list<ReturnJournalEntry>
     * @see https://api.baselinker.com/index.php?method=getOrderReturnJournalList
     */
    public function getOrderReturnJournalList(array $filters = []): array
    {
        $response = $this->client->call('getOrderReturnJournalList', $filters);

        return array_map(ReturnJournalEntry::fromArray(...), $this->items($response, 'logs'));
    }

    /**
     * Runs personal trigger for order returns automatic actions.
     *
     * @param int $returnId Order return identifier from Base order manager
     * @param int $triggerId Identifier of personal trigger from orders automatic actions
     * @see https://api.baselinker.com/index.php?method=runOrderReturnMacroTrigger
     */
    public function runOrderReturnMacroTrigger(int $returnId, int $triggerId): void
    {
        $this->client->call('runOrderReturnMacroTrigger', ['return_id' => $returnId, 'trigger_id' => $triggerId]);
    }

    /**
     * Maximum number of returns returned by one getOrderReturns call.
     */
    public const int PAGE_SIZE = 100;

    /**
     * Lazily iterates order returns by ascending id, starting at the given return id.
     *
     * @param array<string, mixed> $filters Other getOrderReturns filters, for example status_id or date_from.
     * @return LazyCollection<int, OrderReturn>
     */
    public function iterate(int $idFrom = 1, array $filters = []): LazyCollection
    {
        return LazyCollection::make(function () use ($idFrom, $filters) {
            $cursor = $idFrom;

            do {
                $returns = $this->getOrderReturns(['id_from' => $cursor] + $filters);

                foreach ($returns as $return) {
                    yield $return;
                }

                if ($returns === []) {
                    return;
                }

                $cursor = max($cursor, $returns[array_key_last($returns)]->id) + 1;
            } while (count($returns) >= self::PAGE_SIZE);
        });
    }
}
