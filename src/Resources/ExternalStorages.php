<?php

declare(strict_types=1);

namespace Beziworld\BaseLinker\Resources;

use Beziworld\BaseLinker\Data\BatchResult;

/**
 * External storages (shops and wholesalers connected to the account).
 *
 * Every method maps one-to-one to the BaseLinker API method of the same name.
 */
final class ExternalStorages extends Resource
{
    /**
     * Retrieves a category list from an external storage (shop/wholesale) connected to BaseLinker.
     *
     * @param string $storageId Storage ID in format "[type:shop|warehouse]_[id:int]" (e.g
     * @return list<array<string, mixed>>
     * @see https://api.baselinker.com/index.php?method=getExternalStorageCategories
     */
    public function getExternalStorageCategories(string $storageId): array
    {
        $response = $this->client->call('getExternalStorageCategories', ['storage_id' => $storageId]);

        return $this->items($response, 'categories');
    }

    /**
     * Retrieves detailed data of selected products from an external storage (shop/wholesaler) connected to BaseLinker.
     *
     * @param string $storageId Storage ID in format "[type:shop|warehouse]_[id:int]" (e.g
     * @param array<array-key, mixed> $products API field "products". An array of product ID numbers to download
     * @return array<array-key, mixed>
     * @see https://api.baselinker.com/index.php?method=getExternalStorageProductsData
     */
    public function getExternalStorageProductsData(string $storageId, array $products): array
    {
        $response = $this->client->call('getExternalStorageProductsData', ['storage_id' => $storageId, 'products' => $products]);

        return $this->map($response, 'products');
    }

    /**
     * Retrieves detailed data of selected products from an external storage (shop/wholesaler) connected to BaseLinker.
     *
     * @param string $storageId Storage ID in format "[type:shop|warehouse]_[id:int]" (e.g
     * @param array<string, mixed> $filters Filters: filter_category_id, filter_sort, filter_id, filter_sku, filter_ean, filter_asin, filter_name, filter_price_from, filter_price_to, filter_quantity_from, filter_quantity_to, filter_available, page.
     * @return array<array-key, mixed>
     * @see https://api.baselinker.com/index.php?method=getExternalStorageProductsList
     */
    public function getExternalStorageProductsList(string $storageId, array $filters = []): array
    {
        $response = $this->client->call('getExternalStorageProductsList', ['storage_id' => $storageId] + $filters);

        return $this->map($response, 'products');
    }

    /**
     * Retrieves product prices from an external storage (shop/wholesaler) connected to BaseLinker.
     *
     * @param string $storageId Storage ID in format "[type:shop|warehouse]_[id:int]" (e.g
     * @param array<string, mixed> $options Optional fields: page.
     * @return array<array-key, mixed>
     * @see https://api.baselinker.com/index.php?method=getExternalStorageProductsPrices
     */
    public function getExternalStorageProductsPrices(string $storageId, array $options = []): array
    {
        $response = $this->client->call('getExternalStorageProductsPrices', ['storage_id' => $storageId] + $options);

        return $this->map($response, 'products');
    }

    /**
     * Retrieves stock from an external storage (shop/wholesaler) connected to BaseLinker.
     *
     * @param string $storageId Storage ID in format "[type:shop|warehouse]_[id:int]" (e.g
     * @param array<string, mixed> $options Optional fields: page.
     * @return array<array-key, mixed>
     * @see https://api.baselinker.com/index.php?method=getExternalStorageProductsQuantity
     */
    public function getExternalStorageProductsQuantity(string $storageId, array $options = []): array
    {
        $response = $this->client->call('getExternalStorageProductsQuantity', ['storage_id' => $storageId] + $options);

        return $this->map($response, 'products');
    }

    /**
     * Retrieves a list of available external storages (shops, wholesalers) that can be referenced via API.
     *
     * @return list<array<string, mixed>>
     * @see https://api.baselinker.com/index.php?method=getExternalStoragesList
     */
    public function getExternalStoragesList(): array
    {
        $response = $this->client->call('getExternalStoragesList');

        return $this->items($response, 'storages');
    }

    /**
     * Updates stock of products (and their variants) in an external storage (shop or wholesaler); at most 1000 products per call.
     *
     * @param string $storageId Storage ID in format "[type:shop|warehouse]_[id:int]" (e.g
     * @param array<array-key, mixed> $products API field "products". An array of products
     * @see https://api.baselinker.com/index.php?method=updateExternalStorageProductsQuantity
     */
    public function updateExternalStorageProductsQuantity(string $storageId, array $products): BatchResult
    {
        $response = $this->client->call('updateExternalStorageProductsQuantity', ['storage_id' => $storageId, 'products' => $products]);

        return BatchResult::fromArray($response);
    }

    /**
     * Maximum number of products accepted by one quantity update.
     */
    public const int BATCH_SIZE = 1000;

    /**
     * Updates stock in an external storage for any number of products in chunks of 1000 and sums the results.
     *
     * @param list<array{0: int|string, 1: int|string, 2: int}> $products Entries of [product_id, variant_id, quantity].
     */
    public function updateQuantityInChunks(string $storageId, array $products, int $chunkSize = self::BATCH_SIZE): BatchResult
    {
        return $this->batched(
            array_chunk($products, max(1, min(self::BATCH_SIZE, $chunkSize))),
            fn(array $chunk): BatchResult => $this->updateExternalStorageProductsQuantity($storageId, $chunk),
        );
    }
}
