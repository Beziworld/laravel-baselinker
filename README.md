# Laravel BaseLinker

[![tests](https://github.com/beziworld/laravel-baselinker/actions/workflows/tests.yml/badge.svg)](https://github.com/beziworld/laravel-baselinker/actions/workflows/tests.yml)
[![Latest Version](https://img.shields.io/packagist/v/beziworld/laravel-baselinker.svg)](https://packagist.org/packages/beziworld/laravel-baselinker)
[![License](https://img.shields.io/packagist/l/beziworld/laravel-baselinker.svg)](LICENSE.md)

[BaseLinker (Base)](https://base.com) API client for Laravel. One package for orders, product catalogs, warehouses,
shipping, invoices and returns of one or many BaseLinker accounts:

- all **211 API methods** with dedicated PHP methods, plus a generic `call()` for methods added later
- multiple accounts: named connections and runtime tokens stored per tenant
- typed, immutable objects for orders, statuses, sources, packages, catalog products, inventories, warehouses,
  price groups, invoices, returns and journal entries (unix timestamps become `CarbonImmutable`)
- lazy iterators for orders, invoices, returns and catalog products; chunked stock and price updates
- journal polling (order and return events) dispatched as Laravel events, with an artisan command for the scheduler
- labels, protocols, invoices and warehouse documents decoded from base64 into binary strings
- client-side rate limiting per token (100 requests per minute) and the error envelope turned into exceptions

[Polska wersja dokumentacji](README.pl.md)

## Installation

```bash
composer require beziworld/laravel-baselinker
php artisan vendor:publish --tag=baselinker-config   # optional
```

```dotenv
BASELINKER_TOKEN=1234-56789-ABCDEF   # panel: Account & other > My account > API
```

Requires PHP 8.3+ and Laravel 12 or 13. BaseLinker has no sandbox: every call works on the real account.

## Multiple accounts

Each connection in `config/baselinker.php` has its own token; the facade and the injected `BaseLinkerClient`
use the default connection.

```php
'connections' => [
    'default' => ['token' => env('BASELINKER_TOKEN')],
    'wholesale' => ['token' => env('BASELINKER_WHOLESALE_TOKEN')],
],
```

```php
use Beziworld\BaseLinker\Facades\BaseLinker;

BaseLinker::orders()->getOrderStatusList();                  // default connection
BaseLinker::connection('wholesale')->inventory()->getInventories();

// Token stored per tenant (for example in the database); the name identifies the account in journal cursors and events
$client = BaseLinker::using($tenant->baselinker_token, name: 'tenant-'.$tenant->id);
$client->orders()->getOrders(['status_id' => 6624]);
```

## Orders

```php
use Beziworld\BaseLinker\Facades\BaseLinker;

// Iterate confirmed orders from a date: 100 per request, next page from the last date_confirmed + 1 s
foreach (BaseLinker::orders()->iterate(now()->subDays(7), ['filter_order_source' => 'allegro']) as $order) {
    $order->id;                         // 7834521
    $order->dateConfirmed;              // CarbonImmutable
    $order->payment->cashOnDelivery;    // bool
    $order->delivery->method;           // "InPost Paczkomaty"
    $order->delivery->point?->id;       // "KRA010"
    $order->delivery->address->city;
    $order->invoiceAddress->taxId;
    foreach ($order->products as $product) {
        $product->sku; $product->priceGross; $product->quantity;
    }
    $order->totalGross(); $order->isPaid();
    $order->raw;                        // the original array, including fields not mapped
}

BaseLinker::orders()->iterateById(1, ['get_unconfirmed_orders' => true]); // all orders by id
BaseLinker::orders()->find(7834521);                                       // ?Order

// Statuses and sources
$statuses = BaseLinker::orders()->getOrderStatusList();    // list<OrderStatus>
$sources = BaseLinker::orders()->getOrderSources();        // list<OrderSource> (type, id, name)
BaseLinker::orders()->setOrderStatus(7834521, 6625);
BaseLinker::orders()->setOrderStatuses([7834521, 7834522], 6625);

// Fields: only the given keys are changed
BaseLinker::orders()->setOrderFields(7834521, ['admin_comments' => 'Gift wrap', 'extra_field_1' => 'VIP']);

// Add an order
$orderId = BaseLinker::orders()->addOrder([
    'order_status_id' => 6624,
    'date_add' => now()->timestamp,
    'currency' => 'PLN',
    'payment_method' => 'Bank transfer',
    'payment_method_cod' => false,
    'paid' => true,
    'email' => 'jan@example.pl',
    'delivery_method' => 'Courier',
    'delivery_price' => 15.0,
    'delivery_fullname' => 'Jan Kowalski',
    'delivery_address' => 'Dluga 12/4',
    'delivery_postcode' => '00-001',
    'delivery_city' => 'Warszawa',
    'delivery_country_code' => 'PL',
    'want_invoice' => false,
    'products' => [
        ['storage' => 'db', 'storage_id' => 0, 'product_id' => '2847', 'name' => 'Wireless mouse', 'sku' => 'LGT-MX-BLK',
         'price_brutto' => 129.99, 'tax_rate' => 23, 'quantity' => 1, 'weight' => 0.12],
    ],
]);
```

Method names follow the API one-to-one (`getOrders`, `addOrderProduct`, `setOrderPayment`, ...). Identifiers
are typed positional arguments; the remaining fields go in a trailing array with the documented key names.

## Inventory (product catalog)

```php
$inventories = BaseLinker::inventory()->getInventories();        // list<Inventory>
$warehouses = BaseLinker::warehouses()->getInventoryWarehouses(); // list<Warehouse>, $warehouse->code() === "bl_206"
$groups = BaseLinker::inventory()->getInventoryPriceGroups();     // list<PriceGroup>

// All products of a catalog, 1000 per page, keyed by product id
foreach (BaseLinker::inventory()->iterateProducts(306, ['filter_category_id' => 12]) as $id => $product) {
    $product->sku; $product->name; $product->prices; $product->stock;   // ['bl_206' => 5.0]
}

// Full data (text fields, images, variants, ...) for many ids, in chunks
foreach (BaseLinker::inventory()->productsData(306, $ids, ['include_suppliers' => true]) as $id => $product) {
    $product->textFields['description'] ?? null;
    foreach ($product->variants as $variantId => $variant) { $variant->sku; $variant->stock; }
}

// Bulk updates: any number of products, sent in chunks of 1000 with summed counters and warnings
$result = BaseLinker::inventory()->updateStockInChunks(306, [
    2685 => ['bl_206' => 5],
    2686 => ['bl_206' => 3, 'bl_207' => 1],
]);
$result->counter;      // updated products
$result->warnings;     // ['2686' => 'Warehouse bl_207 ...'], keyed by product id
$result->requests;     // number of API calls

BaseLinker::inventory()->updatePricesInChunks(306, [2685 => ['105' => 20.99]]);

// Create or update a product (product_id given = update)
BaseLinker::inventory()->addInventoryProduct(306, ['sku' => 'EPL-432', 'prices' => ['105' => 20.99], 'stock' => ['bl_206' => 5],
    'text_fields' => ['name' => 'Harry Potter', 'description' => '...']]);
```

Stock cannot be written to warehouses created automatically for shops and wholesalers; use
`externalStorages()->updateQuantityInChunks()` for external storages.

## Couriers and labels

```php
$couriers = BaseLinker::couriers()->getCouriersList();
$fields = BaseLinker::couriers()->getCourierFields('inpost');            // form fields and package fields

$package = BaseLinker::couriers()->createPackage(7834521, 'inpost',
    fields: [['id' => 'service', 'value' => 'paczkomaty'], ['id' => 'size', 'value' => 'A']],
    packages: [['weight' => 1.2]],
);
$package->packageId; $package->packageNumber;

// Label as binary: no base64 handling needed
$label = BaseLinker::couriers()->getLabel('inpost', packageId: $package->packageId);
Storage::put('labels/'.$label->filename('label-'.$package->packageId), $label->contents); // pdf, zpl, epl, ...
$label->mimeType();

BaseLinker::couriers()->getOrderPackages(7834521);       // list<Package>, $package->trackingStatus is a TrackingStatus enum
BaseLinker::couriers()->getProtocol('dpd', ['package_ids' => [123, 124]]);
BaseLinker::couriers()->createPackageManual(7834521, 'dpd', '0000123456789U', now()->timestamp);
```

## Invoices and receipts

```php
$invoiceId = BaseLinker::invoices()->addInvoice(7834521, seriesId: 15);

foreach (BaseLinker::invoices()->iterate(1, ['series_id' => 15]) as $invoice) {
    $invoice->number; $invoice->totalGross; $invoice->buyer->taxId; $invoice->isCorrection();
}

$file = BaseLinker::invoices()->getInvoiceFile($invoiceId);   // File: contents (binary PDF), number, filename()
BaseLinker::invoices()->addOrderInvoiceFile($invoiceId, file_get_contents('erp-invoice.pdf'), 'FV 1/11/2026'); // encoded for you
```

## Returns

```php
foreach (BaseLinker::returns()->iterate(1, ['status_id' => 1001]) as $return) {
    $return->orderId; $return->refunded; $return->products[0]->returnReasonId;
}

BaseLinker::returns()->setOrderReturnStatus(5, 1002);
BaseLinker::returns()->setOrderReturnRefund(5, 129.99, now()->timestamp, ['refund_comment' => 'Refunded']);
```

## Journal polling

BaseLinker has no webhooks. Changes are read from the order journal (`getJournalList`, 22 event types) and the
return journal (`getOrderReturnJournalList`, 12 event types), which keep entries for **3 days**.

> The journal must be enabled in the BaseLinker panel, in the API settings of the account. Otherwise the API
> returns an empty list and the command prints a hint.

```php
// routes/console.php
Schedule::command('baselinker:poll')->everyMinute()->withoutOverlapping();
// one connection or the return journal: baselinker:poll wholesale --returns
```

```php
use Beziworld\BaseLinker\Enums\JournalEventType;
use Beziworld\BaseLinker\Events\JournalEventReceived;

Event::listen(function (JournalEventReceived $event) {
    if ($event->entry->type === JournalEventType::StatusChanged) {
        // $event->connection, $event->entry->orderId, $event->entry->objectId (new status id), $event->entry->date
    }
});
```

The last processed log id is stored per connection in the cache (`journal.cache_store`, use a persistent store),
so every run continues where the previous one stopped. Entries are delivered at least once, so listeners
should be idempotent. Filter event types with `journal.order_event_types` / `journal.return_event_types` and
choose the journals with `journal.poll` (`['orders']` by default). For tenant tokens call the poller directly:
`app(JournalPoller::class)->pollOrders(BaseLinker::using($token, 'tenant-7'))`.

## Rate limits

BaseLinker allows 100 requests per minute. The client counts requests per token in the Laravel cache (use a
shared store such as Redis when several servers or workers use one token). When the budget is used up it waits
for the window to reset (`BASELINKER_RATE_LIMIT_STRATEGY=wait`, up to `max_wait` seconds) or throws
`RateLimitExceeded` (`throw`). Requests are never retried automatically: most write methods are not idempotent.

## Errors

The API answers every call with HTTP 200; failures come in the envelope as `status: ERROR`.

| Exception | When | Details |
|---|---|---|
| `ApiException` | `status: ERROR`, HTTP error, non-JSON response, connection failure | `errorCode()` (e.g. `ERROR_BAD_TOKEN`), `errorMessage()`, `apiMethod()`, `httpStatus()`, `isBadToken()` |
| `RateLimitExceeded` | client-side limiter refused the request | `retryAfter()` seconds |
| `InvalidResponse` | a file method returned no file or invalid base64 | |
| `InvalidConfiguration` | missing token, unknown connection, invalid option | |

All extend `Beziworld\BaseLinker\Exceptions\BaseLinkerException`.

## Coverage of the API

Every method listed on [api.baselinker.com](https://api.baselinker.com/) (211) has a dedicated method with the same
name on one of the resources below. Methods published later can be called with
`BaseLinker::call('methodName', [...])`.

| Resource | Methods | API methods |
|---|---|---|
| `orders()` | 29 | `addOrder`, `addOrderBySplit`, `addOrderDuplicate`, `addOrderProduct`, `addOrderStatus`, `addOrderStatusGroup`, `deleteOrderProduct`, `deleteOrderStatus`, `deleteOrderStatusGroup`, `deleteOrders`, `getJournalList`, `getOrderExtraFields`, `getOrderPaymentsHistory`, `getOrderPickPackHistory`, `getOrderPrintoutTemplates`, `getOrderSources`, `getOrderStatusGroups`, `getOrderStatusList`, `getOrderTransactionData`, `getOrders`, `getOrdersByEmail`, `getOrdersByPhone`, `runOrderMacroTrigger`, `setOrderFields`, `setOrderPayment`, `setOrderProductFields`, `setOrderStatus`, `setOrderStatuses`, `setOrdersMerge` |
| `pickPack()` | 10 | `addOrderPickList`, `addPickPackCart`, `addPickPackOrdersToCart`, `deletePickPackCart`, `deletePickPackCartOrders`, `deletePickPackOrderFromCart`, `getOrderPickLists`, `getPickPackCartOrders`, `getPickPackCarts`, `getPickPackOrderCart` |
| `invoices()` | 6 | `addInvoice`, `addInvoiceCorrection`, `addOrderInvoiceFile`, `getInvoiceFile`, `getInvoices`, `getSeries` |
| `receipts()` | 6 | `addOrderReceiptFile`, `addReceipt`, `getNewReceipts`, `getReceipt`, `getReceipts`, `setOrderReceipt` |
| `returns()` | 21 | `addOrderReturn`, `addOrderReturnProduct`, `addOrderReturnStatus`, `addOrderReturnStatusGroup`, `deleteOrderReturnProduct`, `deleteOrderReturnStatus`, `deleteOrderReturnStatusGroup`, `getOrderReturnExtraFields`, `getOrderReturnJournalList`, `getOrderReturnPaymentsHistory`, `getOrderReturnProductStatuses`, `getOrderReturnReasonsList`, `getOrderReturnStatusGroups`, `getOrderReturnStatusList`, `getOrderReturns`, `runOrderReturnMacroTrigger`, `setOrderReturnFields`, `setOrderReturnProductFields`, `setOrderReturnRefund`, `setOrderReturnStatus`, `setOrderReturnStatuses` |
| `couriers()` | 16 | `createPackage`, `createPackageManual`, `deleteCourierPackage`, `getCourierAccounts`, `getCourierDocument`, `getCourierFields`, `getCourierPackagesStatusHistory`, `getCourierServices`, `getCouriersList`, `getLabel`, `getOrderPackages`, `getPackageDetails`, `getPricePackage`, `getProtocol`, `getRequestParcelPickupFields`, `runRequestParcelPickup` |
| `crm()` | 11 | `addCrmClient`, `addCrmClientStatus`, `addCrmClientStatusGroup`, `deleteCrmClient`, `deleteCrmClientStatus`, `deleteCrmClientStatusGroup`, `getCrmClientData`, `getCrmClientExtraFields`, `getCrmClientStatusGroups`, `getCrmClientStatuses`, `getCrmClients` |
| `inventory()` | 35 | `addInventory`, `addInventoryCategory`, `addInventoryManufacturer`, `addInventoryPriceGroup`, `addInventoryProduct`, `addInventoryPromotion`, `addInventoryTag`, `deleteInventory`, `deleteInventoryCategory`, `deleteInventoryManufacturer`, `deleteInventoryPriceGroup`, `deleteInventoryProduct`, `deleteInventoryTag`, `getInventories`, `getInventoryAvailableTextFieldKeys`, `getInventoryCategories`, `getInventoryExtraFields`, `getInventoryIntegrations`, `getInventoryManufacturers`, `getInventoryParameters`, `getInventoryPriceGroups`, `getInventoryPrintoutTemplates`, `getInventoryProductLogs`, `getInventoryProductsData`, `getInventoryProductsList`, `getInventoryProductsPrices`, `getInventoryProductsStock`, `getInventoryPromotionsList`, `getInventoryShippingTemplates`, `getInventoryTags`, `getSyncLogList`, `runFulfillmentLinkProducts`, `runProductMacroTrigger`, `updateInventoryProductsPrices`, `updateInventoryProductsStock` |
| `warehouses()` | 17 | `addInventoryWarehouse`, `addInventoryWarehouseLocation`, `addInventoryWarehouseLocationType`, `addInventoryWarehouseRack`, `addInventoryWarehouseZone`, `deleteInventoryWarehouse`, `deleteInventoryWarehouseLocation`, `deleteInventoryWarehouseLocationType`, `deleteInventoryWarehouseRack`, `deleteInventoryWarehouseZone`, `getInventoryMapDetails`, `getInventoryMaps`, `getInventoryWarehouseLocationTypes`, `getInventoryWarehouseLocations`, `getInventoryWarehouseRacks`, `getInventoryWarehouseZones`, `getInventoryWarehouses` |
| `documents()` | 8 | `addInventoryDocument`, `addInventoryDocumentFile`, `addInventoryDocumentItems`, `getInventoryDocumentFile`, `getInventoryDocumentItems`, `getInventoryDocumentSeries`, `getInventoryDocuments`, `setInventoryDocumentStatusConfirmed` |
| `purchaseOrders()` | 16 | `addInventoryPayer`, `addInventoryPurchaseOrder`, `addInventoryPurchaseOrderFile`, `addInventoryPurchaseOrderItems`, `addInventorySupplier`, `deleteInventoryPayer`, `deleteInventoryPurchaseOrderItems`, `deleteInventorySupplier`, `getInventoryPayers`, `getInventoryPurchaseOrderFile`, `getInventoryPurchaseOrderItems`, `getInventoryPurchaseOrderLogs`, `getInventoryPurchaseOrderSeries`, `getInventoryPurchaseOrders`, `getInventorySuppliers`, `setInventoryPurchaseOrderStatus` |
| `transfers()` | 6 | `addInventoryTransfer`, `addInventoryTransferItems`, `getInventoryTransferItems`, `getInventoryTransferSeries`, `getInventoryTransfers`, `setInventoryTransferStatus` |
| `stocktakes()` | 6 | `addInventoryStocktake`, `addInventoryStocktakeItems`, `getInventoryStocktakeItems`, `getInventoryStocktakeSeries`, `getInventoryStocktakes`, `setInventoryStocktakeStatus` |
| `fulfillment()` | 6 | `addInventoryFulfillmentDelivery`, `addInventoryFulfillmentDeliveryItems`, `getInventoryFulfillmentDeliveries`, `getInventoryFulfillmentDeliveryItems`, `getInventoryFulfillmentDeliveryLabels`, `runInventoryFulfillmentDeliverySubmission` |
| `connect()` | 5 | `addConnectContractorCreditSettlement`, `getConnectContractorCreditHistory`, `getConnectIntegrationContractors`, `getConnectIntegrations`, `setConnectContractorCreditLimit` |
| `externalStorages()` | 7 | `getExternalStorageCategories`, `getExternalStorageProductsData`, `getExternalStorageProductsList`, `getExternalStorageProductsPrices`, `getExternalStorageProductsQuantity`, `getExternalStoragesList`, `updateExternalStorageProductsQuantity` |
| `listingAgents()` | 6 | `getListingAgentQueueItems`, `getListingAgentRunItems`, `getListingAgentRunsList`, `getListingAgentSettingsHistory`, `getListingAgentsData`, `getListingAgentsList` |

## Development

```bash
composer install
composer ci      # pint --test, phpstan (level 8, strict rules), pest
```

Every API method has a test asserting the `X-BLToken` header, the `method` field and the `parameters` JSON.

## Author

Created and maintained by **Kamil Ćwiertnia**, [beziworld.eu](https://beziworld.eu).

- E-mail: [biuro@beziworld.eu](mailto:biuro@beziworld.eu)
- Issues: [GitHub issues](https://github.com/beziworld/laravel-baselinker/issues)

Need an integration with BaseLinker or a marketplace? Get in touch through [beziworld.eu](https://beziworld.eu).

## License

MIT. See [LICENSE.md](LICENSE.md). This package is not affiliated with BaseLinker or Base.com.
