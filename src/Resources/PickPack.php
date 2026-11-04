<?php

declare(strict_types=1);

namespace Beziworld\BaseLinker\Resources;

/**
 * Paper pick lists and PickPack carts.
 *
 * Every method maps one-to-one to the BaseLinker API method of the same name.
 */
final class PickPack extends Resource
{
    /**
     * Creates a paper pick list for up to 100 orders belonging to the authenticated user.
     *
     * @param array<array-key, mixed> $orderIds API field "order_ids". Order identifiers from the Base order manager
     * @see https://api.baselinker.com/index.php?method=addOrderPickList
     */
    public function addOrderPickList(array $orderIds): ?int
    {
        $response = $this->client->call('addOrderPickList', ['order_ids' => $orderIds]);

        return $this->int($response, 'pick_list_id');
    }

    /**
     * Returns up to 100 paper pick lists belonging to the authenticated user.
     *
     * @param array<string, mixed> $filters Filters: pick_list_id, id_from.
     * @return list<array<string, mixed>>
     * @see https://api.baselinker.com/index.php?method=getOrderPickLists
     */
    public function getOrderPickLists(array $filters = []): array
    {
        $response = $this->client->call('getOrderPickLists', $filters);

        return $this->items($response, 'picklists');
    }

    /**
     * Creates a new PickPack cart or updates an existing one.
     *
     * @param array<string, mixed> $cart Fields: cart_id, name, color.
     * @see https://api.baselinker.com/index.php?method=addPickPackCart
     */
    public function addPickPackCart(array $cart): ?int
    {
        $response = $this->client->call('addPickPackCart', $cart);

        return $this->int($response, 'cart_id');
    }

    /**
     * Assigns multiple orders to a specific PickPack cart in a single API call.
     *
     * @param array<array-key, mixed> $orderIds API field "order_ids". Array of order identifiers to be assigned to the cart (maximum 20 orders per call)
     * @param int $cartId Cart identifier where the orders should be assigned
     * @return array<string, mixed>
     * @see https://api.baselinker.com/index.php?method=addPickPackOrdersToCart
     */
    public function addPickPackOrdersToCart(array $orderIds, int $cartId): array
    {
        return $this->client->call('addPickPackOrdersToCart', ['order_ids' => $orderIds, 'cart_id' => $cartId]);
    }

    /**
     * Deletes a PickPack cart from the user's account.
     *
     * @param int $cartId Cart identifier to be deleted
     * @see https://api.baselinker.com/index.php?method=deletePickPackCart
     */
    public function deletePickPackCart(int $cartId): void
    {
        $this->client->call('deletePickPackCart', ['cart_id' => $cartId]);
    }

    /**
     * Removes all orders from a specific PickPack cart.
     *
     * @param int $cartId Cart identifier to be cleared of all orders
     * @see https://api.baselinker.com/index.php?method=deletePickPackCartOrders
     */
    public function deletePickPackCartOrders(int $cartId): ?int
    {
        $response = $this->client->call('deletePickPackCartOrders', ['cart_id' => $cartId]);

        return $this->int($response, 'orders_removed');
    }

    /**
     * Removes a specific order from its assigned PickPack cart.
     *
     * @param int $orderId Order identifier to be removed from its cart
     * @see https://api.baselinker.com/index.php?method=deletePickPackOrderFromCart
     */
    public function deletePickPackOrderFromCart(int $orderId): void
    {
        $this->client->call('deletePickPackOrderFromCart', ['order_id' => $orderId]);
    }

    /**
     * Retrieves all orders assigned to a specific PickPack cart.
     *
     * @param int $cartId Cart identifier to retrieve orders from
     * @return array<string, mixed>
     * @see https://api.baselinker.com/index.php?method=getPickPackCartOrders
     */
    public function getPickPackCartOrders(int $cartId): array
    {
        return $this->client->call('getPickPackCartOrders', ['cart_id' => $cartId]);
    }

    /**
     * Retrieves a list of all PickPack carts belonging to the authenticated user.
     *
     * @return list<array<string, mixed>>
     * @see https://api.baselinker.com/index.php?method=getPickPackCarts
     */
    public function getPickPackCarts(): array
    {
        $response = $this->client->call('getPickPackCarts');

        return $this->items($response, 'carts');
    }

    /**
     * Retrieves cart assignment information for a single order in the PickPack system.
     *
     * @param int $orderId Order identifier to check cart assignment for
     * @return array<string, mixed>
     * @see https://api.baselinker.com/index.php?method=getPickPackOrderCart
     */
    public function getPickPackOrderCart(int $orderId): array
    {
        return $this->client->call('getPickPackOrderCart', ['order_id' => $orderId]);
    }
}
