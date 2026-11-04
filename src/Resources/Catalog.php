<?php

declare(strict_types=1);

namespace Beziworld\BaseLinker\Resources;

use Beziworld\BaseLinker\Data\BatchResult;
use Beziworld\BaseLinker\Data\Inventory;
use Beziworld\BaseLinker\Data\InventoryProduct;
use Beziworld\BaseLinker\Data\PriceGroup;
use Illuminate\Support\LazyCollection;

/**
 * Product catalogs (inventories): catalogs, price groups, categories, manufacturers, products, stock, prices, tags and promotions.
 *
 * Every method maps one-to-one to the BaseLinker API method of the same name.
 */
final class Catalog extends Resource
{
    /**
     * Adds a catalog (inventory) or updates an existing one.
     *
     * @param array<string, mixed> $inventory Fields: inventory_id, name, description, languages, default_language, price_groups, default_price_group, warehouses, default_warehouse, reservations.
     * @see https://api.baselinker.com/index.php?method=addInventory
     */
    public function addInventory(array $inventory): ?int
    {
        $response = $this->client->call('addInventory', $inventory);

        return $this->int($response, 'inventory_id');
    }

    /**
     * Deletes a catalog from Base storage.
     *
     * @param int $inventoryId Catalog ID
     * @see https://api.baselinker.com/index.php?method=deleteInventory
     */
    public function deleteInventory(int $inventoryId): void
    {
        $this->client->call('deleteInventory', ['inventory_id' => $inventoryId]);
    }

    /**
     * Retrieves a list of catalogs available in the Base storage.
     *
     * @return list<Inventory>
     * @see https://api.baselinker.com/index.php?method=getInventories
     */
    public function getInventories(): array
    {
        $response = $this->client->call('getInventories');

        return array_map(Inventory::fromArray(...), $this->items($response, 'inventories'));
    }

    /**
     * Creates a price group in Base storage.
     *
     * @param array<string, mixed> $priceGroup Fields: price_group_id, name, description, currency, price_group_type, source_price_group_id, price_multiplier, price_addition, is_bundle_price_calculated, bundle_price_multiplier, bundle_price_addition.
     * @see https://api.baselinker.com/index.php?method=addInventoryPriceGroup
     */
    public function addInventoryPriceGroup(array $priceGroup): ?int
    {
        $response = $this->client->call('addInventoryPriceGroup', $priceGroup);

        return $this->int($response, 'price_group_id');
    }

    /**
     * Removes the price group from Base storage.
     *
     * @param int $priceGroupId Price group identifier
     * @see https://api.baselinker.com/index.php?method=deleteInventoryPriceGroup
     */
    public function deleteInventoryPriceGroup(int $priceGroupId): void
    {
        $this->client->call('deleteInventoryPriceGroup', ['price_group_id' => $priceGroupId]);
    }

    /**
     * Retrieves price groups existing in Base storage.
     *
     * @return list<PriceGroup>
     * @see https://api.baselinker.com/index.php?method=getInventoryPriceGroups
     */
    public function getInventoryPriceGroups(): array
    {
        $response = $this->client->call('getInventoryPriceGroups');

        return array_map(PriceGroup::fromArray(...), $this->items($response, 'price_groups'));
    }

    /**
     * Adds a category to the Base catalog.
     *
     * @param array<string, mixed> $category Fields: inventory_id, category_id, name, parent_id.
     * @see https://api.baselinker.com/index.php?method=addInventoryCategory
     */
    public function addInventoryCategory(array $category): ?int
    {
        $response = $this->client->call('addInventoryCategory', $category);

        return $this->int($response, 'category_id');
    }

    /**
     * Removes categories from Base warehouse.
     *
     * @param int $categoryId The number of the category to be removed in the Base storage
     * @see https://api.baselinker.com/index.php?method=deleteInventoryCategory
     */
    public function deleteInventoryCategory(int $categoryId): void
    {
        $this->client->call('deleteInventoryCategory', ['category_id' => $categoryId]);
    }

    /**
     * Retrieves a list of categories for a Base catalog.
     *
     * @param ?int $inventoryId Catalog ID
     * @return list<array<string, mixed>>
     * @see https://api.baselinker.com/index.php?method=getInventoryCategories
     */
    public function getInventoryCategories(?int $inventoryId = null): array
    {
        $response = $this->client->call('getInventoryCategories', $this->withoutNulls(['inventory_id' => $inventoryId]));

        return $this->items($response, 'categories');
    }

    /**
     * Adds a manufacturer to the Base catalog.
     *
     * @param array<string, mixed> $manufacturer Fields: manufacturer_id, name, manufacturer_name, manufacturer_photo, manufacturer_url, manufacturer_street, manufacturer_postcode, manufacturer_city, manufacturer_state, manufacturer_country_code, manufacturer_email, manufacturer_phone.
     * @see https://api.baselinker.com/index.php?method=addInventoryManufacturer
     */
    public function addInventoryManufacturer(array $manufacturer): ?int
    {
        $response = $this->client->call('addInventoryManufacturer', $manufacturer);

        return $this->int($response, 'manufacturer_id');
    }

    /**
     * Removes manufacturer from Base catalog.
     *
     * @param int $manufacturerId The ID of the manufacturer removed from Base warehouse
     * @see https://api.baselinker.com/index.php?method=deleteInventoryManufacturer
     */
    public function deleteInventoryManufacturer(int $manufacturerId): void
    {
        $this->client->call('deleteInventoryManufacturer', ['manufacturer_id' => $manufacturerId]);
    }

    /**
     * Retrieves a list of manufacturers for a Base catalog.
     *
     * @param array<string, mixed> $options Optional fields: page.
     * @return list<array<string, mixed>>
     * @see https://api.baselinker.com/index.php?method=getInventoryManufacturers
     */
    public function getInventoryManufacturers(array $options = []): array
    {
        $response = $this->client->call('getInventoryManufacturers', $options);

        return $this->items($response, 'manufacturers');
    }

    /**
     * Adds a new product to Base catalog.
     *
     * @param int $inventoryId Catalog ID
     * @param array<string, mixed> $product Fields: product_id, parent_id, is_bundle, sku, ean, ean_additional, asin, tags, tax_rate, weight, height, width, length, average_cost, star, manufacturer_id, category_id, prices, stock, thresholds, locations, text_fields, images, videos, media_options, links, bundle_products, suppliers, relations, shipping_template.
     * @return array<string, mixed>
     * @see https://api.baselinker.com/index.php?method=addInventoryProduct
     */
    public function addInventoryProduct(int $inventoryId, array $product = []): array
    {
        return $this->client->call('addInventoryProduct', ['inventory_id' => $inventoryId] + $product);
    }

    /**
     * Removes the product from Base catalog.
     *
     * @param int $productId Base inventory product identifier
     * @see https://api.baselinker.com/index.php?method=deleteInventoryProduct
     */
    public function deleteInventoryProduct(int $productId): void
    {
        $this->client->call('deleteInventoryProduct', ['product_id' => $productId]);
    }

    /**
     * Returns predefined inventory parameters with all stored translations, existing choice options, and parameter-group mappings.
     *
     * @return array<string, mixed>
     * @see https://api.baselinker.com/index.php?method=getInventoryParameters
     */
    public function getInventoryParameters(): array
    {
        return $this->client->call('getInventoryParameters');
    }

    /**
     * Retrieves a list of events related to product change (or their variants) in the Base catalog.
     *
     * @param int $productId Product identifier
     * @param array<string, mixed> $filters Filters: date_from, date_to, log_type, sort, page.
     * @return list<array<string, mixed>>
     * @see https://api.baselinker.com/index.php?method=getInventoryProductLogs
     */
    public function getInventoryProductLogs(int $productId, array $filters = []): array
    {
        $response = $this->client->call('getInventoryProductLogs', ['product_id' => $productId] + $filters);

        return $this->items($response, 'logs');
    }

    /**
     * Retrieves detailed data for selected products from the Base inventory.
     *
     * @param int $inventoryId Catalog ID
     * @param array<array-key, mixed> $products API field "products". An array of product ID numbers to download
     * @param array<string, mixed> $options Optional fields: include_erp_units, include_wms_units, include_additional_eans, include_suppliers, include_relations, include_marketplace_categories, include_shipping_templates, include_channels_media.
     * @return array<int, InventoryProduct>
     * @see https://api.baselinker.com/index.php?method=getInventoryProductsData
     */
    public function getInventoryProductsData(int $inventoryId, array $products, array $options = []): array
    {
        $response = $this->client->call('getInventoryProductsData', ['inventory_id' => $inventoryId, 'products' => $products] + $options);

        return $this->keyed($response, 'products', InventoryProduct::fromArray(...));
    }

    /**
     * Retrieves basic data of products from a catalog, 1000 per page.
     *
     * @param int $inventoryId Catalog ID
     * @param array<string, mixed> $filters Filters: filter_id, filter_category_id, filter_tag, filter_ean, filter_sku, filter_name, filter_price_from, filter_price_to, filter_stock_from, filter_stock_to, page, filter_sort, filter_locations, include_variants.
     * @return array<int, InventoryProduct>
     * @see https://api.baselinker.com/index.php?method=getInventoryProductsList
     */
    public function getInventoryProductsList(int $inventoryId, array $filters = []): array
    {
        $response = $this->client->call('getInventoryProductsList', ['inventory_id' => $inventoryId] + $filters);

        return $this->keyed($response, 'products', InventoryProduct::fromArray(...));
    }

    /**
     * Retrieves the gross prices of products from Base inventories.
     *
     * @param int $inventoryId Catalog ID
     * @param array<string, mixed> $options Optional fields: page.
     * @return array<array-key, mixed>
     * @see https://api.baselinker.com/index.php?method=getInventoryProductsPrices
     */
    public function getInventoryProductsPrices(int $inventoryId, array $options = []): array
    {
        $response = $this->client->call('getInventoryProductsPrices', ['inventory_id' => $inventoryId] + $options);

        return $this->map($response, 'products');
    }

    /**
     * Retrieves stock and reservations data of products from Base catalogs, optionally narrowed down with product filters.
     *
     * @param int $inventoryId Catalog ID
     * @param array<string, mixed> $filters Filters: filter_id, filter_category_id, filter_ean, filter_sku, filter_name, filter_asin, filter_stock_from, filter_stock_to, page, filter_sort, filter_locations.
     * @return array<array-key, mixed>
     * @see https://api.baselinker.com/index.php?method=getInventoryProductsStock
     */
    public function getInventoryProductsStock(int $inventoryId, array $filters = []): array
    {
        $response = $this->client->call('getInventoryProductsStock', ['inventory_id' => $inventoryId] + $filters);

        return $this->map($response, 'products');
    }

    /**
     * Synchronously links selected inventory products to a fulfillment warehouse using its configured product extra field.
     *
     * @param int $fulfillmentWarehouseId Fulfillment warehouse identifier
     * @param array<array-key, mixed> $productIds API field "product_ids". Inventory product identifiers
     * @return array<string, mixed>
     * @see https://api.baselinker.com/index.php?method=runFulfillmentLinkProducts
     */
    public function runFulfillmentLinkProducts(int $fulfillmentWarehouseId, array $productIds): array
    {
        return $this->client->call('runFulfillmentLinkProducts', ['fulfillment_warehouse_id' => $fulfillmentWarehouseId, 'product_ids' => $productIds]);
    }

    /**
     * Runs personal trigger for products automatic actions.
     *
     * @param int $productId Product identifier from Base product manager
     * @param int $triggerId Identifier of personal trigger from products automatic actions
     * @see https://api.baselinker.com/index.php?method=runProductMacroTrigger
     */
    public function runProductMacroTrigger(int $productId, int $triggerId): void
    {
        $this->client->call('runProductMacroTrigger', ['product_id' => $productId, 'trigger_id' => $triggerId]);
    }

    /**
     * Updates gross prices of products (and their variants) in a catalog; at most 1000 products per call.
     *
     * @param int $inventoryId Catalog ID
     * @param array<array-key, mixed> $products API field "products". An array of products, where the key is a product ID and the value is a list of prices
     * @see https://api.baselinker.com/index.php?method=updateInventoryProductsPrices
     */
    public function updateInventoryProductsPrices(int $inventoryId, array $products): BatchResult
    {
        $response = $this->client->call('updateInventoryProductsPrices', ['inventory_id' => $inventoryId, 'products' => $products]);

        return BatchResult::fromArray($response);
    }

    /**
     * Updates stock of products (and their variants) in a catalog; at most 1000 products per call.
     *
     * @param int $inventoryId Catalog ID
     * @param array<array-key, mixed> $products API field "products". An array of products, where the key is a product ID and the value is a list of stocks
     * @see https://api.baselinker.com/index.php?method=updateInventoryProductsStock
     */
    public function updateInventoryProductsStock(int $inventoryId, array $products): BatchResult
    {
        $response = $this->client->call('updateInventoryProductsStock', ['inventory_id' => $inventoryId, 'products' => $products]);

        return BatchResult::fromArray($response);
    }

    /**
     * Adds a tag to the Base product catalog.
     *
     * @param array<string, mixed> $tag Fields: tag_id, name.
     * @see https://api.baselinker.com/index.php?method=addInventoryTag
     */
    public function addInventoryTag(array $tag): ?int
    {
        $response = $this->client->call('addInventoryTag', $tag);

        return $this->int($response, 'tag_id');
    }

    /**
     * Removes a tag from the Base product catalog.
     *
     * @param int $tagId The ID of the tag to be removed
     * @see https://api.baselinker.com/index.php?method=deleteInventoryTag
     */
    public function deleteInventoryTag(int $tagId): void
    {
        $this->client->call('deleteInventoryTag', ['tag_id' => $tagId]);
    }

    /**
     * Retrieves a list of tags for a Base catalog.
     *
     * @return list<array<string, mixed>>
     * @see https://api.baselinker.com/index.php?method=getInventoryTags
     */
    public function getInventoryTags(): array
    {
        $response = $this->client->call('getInventoryTags');

        return $this->items($response, 'tags');
    }

    /**
     * Creates or updates an account-level catalog promotion.
     *
     * @param array<string, mixed> $promotion Fields: promotion_id, name, is_enabled, products_filter_type, products_filter_data, is_include_subcategories, source_price_group_id, target_price_group_id, duration_type, date_start, date_end, price_multiplier, price_addition, price_round, csv_identifier.
     * @see https://api.baselinker.com/index.php?method=addInventoryPromotion
     */
    public function addInventoryPromotion(array $promotion): ?int
    {
        $response = $this->client->call('addInventoryPromotion', $promotion);

        return $this->int($response, 'promotion_id');
    }

    /**
     * Returns a list of product text fields that can be overwritten for specific integration.
     *
     * @param int $inventoryId Catalog ID
     * @return array<array-key, mixed>
     * @see https://api.baselinker.com/index.php?method=getInventoryAvailableTextFieldKeys
     */
    public function getInventoryAvailableTextFieldKeys(int $inventoryId): array
    {
        $response = $this->client->call('getInventoryAvailableTextFieldKeys', ['inventory_id' => $inventoryId]);

        return $this->map($response, 'text_field_keys');
    }

    /**
     * Retrieves a list of extra fields for a BaseLinker catalog.
     *
     * @return list<array<string, mixed>>
     * @see https://api.baselinker.com/index.php?method=getInventoryExtraFields
     */
    public function getInventoryExtraFields(): array
    {
        $response = $this->client->call('getInventoryExtraFields');

        return $this->items($response, 'extra_fields');
    }

    /**
     * Returns a list of integrations where text values in the catalog can be overwritten.
     *
     * @param int $inventoryId Catalog ID
     * @return array<array-key, mixed>
     * @see https://api.baselinker.com/index.php?method=getInventoryIntegrations
     */
    public function getInventoryIntegrations(int $inventoryId): array
    {
        $response = $this->client->call('getInventoryIntegrations', ['inventory_id' => $inventoryId]);

        return $this->map($response, 'integrations');
    }

    /**
     * Returns a list of all configured printout templates available for inventory (products).
     *
     * @return list<array<string, mixed>>
     * @see https://api.baselinker.com/index.php?method=getInventoryPrintoutTemplates
     */
    public function getInventoryPrintoutTemplates(): array
    {
        $response = $this->client->call('getInventoryPrintoutTemplates');

        return $this->items($response, 'printouts');
    }

    /**
     * Returns account-level catalog promotion definitions in priority order with pagination.
     *
     * @param array<string, mixed> $options Optional fields: page.
     * @return list<array<string, mixed>>
     * @see https://api.baselinker.com/index.php?method=getInventoryPromotionsList
     */
    public function getInventoryPromotionsList(array $options = []): array
    {
        $response = $this->client->call('getInventoryPromotionsList', $options);

        return $this->items($response, 'promotions');
    }

    /**
     * Returns a list of available shipping templates (delivery schemas) for all connected marketplace integrations.
     *
     * @return list<array<string, mixed>>
     * @see https://api.baselinker.com/index.php?method=getInventoryShippingTemplates
     */
    public function getInventoryShippingTemplates(): array
    {
        $response = $this->client->call('getInventoryShippingTemplates');

        return $this->items($response, 'shipping_templates');
    }

    /**
     * Returns up to 1,000 price and stock synchronization log entries for the authenticated user.
     *
     * @param array<string, mixed> $filters Filters: last_log_id, source, errors_only.
     * @return list<array<string, mixed>>
     * @see https://api.baselinker.com/index.php?method=getSyncLogList
     */
    public function getSyncLogList(array $filters = []): array
    {
        $response = $this->client->call('getSyncLogList', $filters);

        return $this->items($response, 'logs');
    }

    /**
     * Number of products returned by one getInventoryProductsList page.
     */
    public const int PAGE_SIZE = 1000;

    /**
     * Maximum number of products accepted by one stock or price update.
     */
    public const int BATCH_SIZE = 1000;

    /**
     * Lazily iterates the products of a catalog page by page, keyed by product id.
     *
     * @param array<string, mixed> $filters Other getInventoryProductsList filters, for example filter_category_id.
     * @return LazyCollection<int, InventoryProduct>
     */
    public function iterateProducts(int $inventoryId, array $filters = []): LazyCollection
    {
        return LazyCollection::make(function () use ($inventoryId, $filters) {
            $page = max(1, (int) ($filters['page'] ?? 1));

            do {
                $products = $this->getInventoryProductsList($inventoryId, ['page' => $page] + $filters);

                foreach ($products as $id => $product) {
                    yield $id => $product;
                }

                $page++;
            } while (count($products) >= self::PAGE_SIZE);
        });
    }

    /**
     * Lazily loads full data of many products, in chunks of the given size, keyed by product id.
     *
     * @param list<int> $productIds
     * @param array<string, mixed> $options getInventoryProductsData flags, for example include_suppliers.
     * @return LazyCollection<int, InventoryProduct>
     */
    public function productsData(int $inventoryId, array $productIds, array $options = [], int $chunkSize = self::BATCH_SIZE): LazyCollection
    {
        return LazyCollection::make(function () use ($inventoryId, $productIds, $options, $chunkSize) {
            foreach (array_chunk($productIds, max(1, $chunkSize)) as $chunk) {
                foreach ($this->getInventoryProductsData($inventoryId, $chunk, $options) as $id => $product) {
                    yield $id => $product;
                }
            }
        });
    }

    /**
     * Updates stock of any number of products in chunks of 1000 and sums the results.
     *
     * @param array<int|string, array<string, int|float>> $stock Quantities keyed by product or variant id, then by warehouse code (for example "bl_123").
     */
    public function updateStockInChunks(int $inventoryId, array $stock, int $chunkSize = self::BATCH_SIZE): BatchResult
    {
        return $this->batched(
            array_chunk($stock, max(1, min(self::BATCH_SIZE, $chunkSize)), true),
            fn(array $chunk): BatchResult => $this->updateInventoryProductsStock($inventoryId, $chunk),
        );
    }

    /**
     * Updates gross prices of any number of products in chunks of 1000 and sums the results.
     *
     * @param array<int|string, array<int|string, int|float>> $prices Prices keyed by product or variant id, then by price group id.
     */
    public function updatePricesInChunks(int $inventoryId, array $prices, int $chunkSize = self::BATCH_SIZE): BatchResult
    {
        return $this->batched(
            array_chunk($prices, max(1, min(self::BATCH_SIZE, $chunkSize)), true),
            fn(array $chunk): BatchResult => $this->updateInventoryProductsPrices($inventoryId, $chunk),
        );
    }
}
