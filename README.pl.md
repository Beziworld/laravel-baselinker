# Laravel BaseLinker

[![tests](https://github.com/beziworld/laravel-baselinker/actions/workflows/tests.yml/badge.svg)](https://github.com/beziworld/laravel-baselinker/actions/workflows/tests.yml)
[![Latest Version](https://img.shields.io/packagist/v/beziworld/laravel-baselinker.svg)](https://packagist.org/packages/beziworld/laravel-baselinker)
[![License](https://img.shields.io/packagist/l/beziworld/laravel-baselinker.svg)](LICENSE.md)

Klient API [BaseLinker (Base)](https://base.com) dla Laravela. Jeden pakiet do zamówień, katalogów produktów,
magazynów, wysyłek, faktur i zwrotów jednego lub wielu kont BaseLinker:

- wszystkie **211 metod API** z dedykowanymi metodami PHP oraz ogólne `call()` dla metod dodanych później
- wiele kont: nazwane połączenia i tokeny przechowywane per tenant
- typowane, niemutowalne obiekty: zamówienia, statusy, źródła, paczki, produkty katalogu, katalogi, magazyny,
  grupy cenowe, faktury, zwroty i wpisy dziennika (znaczniki unix zamieniane na `CarbonImmutable`)
- leniwe iteratory zamówień, faktur, zwrotów i produktów; aktualizacje stanów i cen w paczkach po 1000
- odpytywanie dziennika zdarzeń (zamówienia i zwroty) z eventami Laravela i komendą artisan dla harmonogramu
- etykiety, protokoły, faktury i dokumenty magazynowe dekodowane z base64 do postaci binarnej
- limiter zapytań per token (100 na minutę) i koperta błędu zamieniana na wyjątki

[English documentation](README.md)

## Instalacja

```bash
composer require beziworld/laravel-baselinker
php artisan vendor:publish --tag=baselinker-config   # opcjonalnie
```

```dotenv
BASELINKER_TOKEN=1234-56789-ABCDEF   # panel: Konto i inne > Moje konto > API
```

Wymaga PHP 8.3+ oraz Laravela 12 lub 13. BaseLinker nie ma środowiska testowego: każde wywołanie działa na
prawdziwym koncie.

## Wiele kont

Każde połączenie w `config/baselinker.php` ma własny token; fasada i wstrzykiwany `BaseLinkerClient` korzystają
z połączenia domyślnego.

```php
'connections' => [
    'default' => ['token' => env('BASELINKER_TOKEN')],
    'hurt' => ['token' => env('BASELINKER_HURT_TOKEN')],
],
```

```php
use Beziworld\BaseLinker\Facades\BaseLinker;

BaseLinker::orders()->getOrderStatusList();               // połączenie domyślne
BaseLinker::connection('hurt')->inventory()->getInventories();

// Token zapisany per tenant (np. w bazie); nazwa identyfikuje konto w kursorach dziennika i w eventach
$client = BaseLinker::using($tenant->baselinker_token, name: 'tenant-'.$tenant->id);
$client->orders()->getOrders(['status_id' => 6624]);
```

## Zamówienia

```php
// Potwierdzone zamówienia od daty: 100 na zapytanie, kolejna strona od ostatniego date_confirmed + 1 s
foreach (BaseLinker::orders()->iterate(now()->subDays(7), ['filter_order_source' => 'allegro']) as $order) {
    $order->id;                         // 7834521
    $order->dateConfirmed;              // CarbonImmutable
    $order->payment->cashOnDelivery;    // bool (pobranie)
    $order->delivery->method;           // "InPost Paczkomaty"
    $order->delivery->point?->id;       // "KRA010"
    $order->delivery->address->city;
    $order->invoiceAddress->taxId;      // NIP
    foreach ($order->products as $product) {
        $product->sku; $product->priceGross; $product->quantity;
    }
    $order->totalGross(); $order->isPaid();
    $order->raw;                        // oryginalna tablica, także pola niezmapowane
}

BaseLinker::orders()->iterateById(1, ['get_unconfirmed_orders' => true]); // wszystkie zamówienia po id
BaseLinker::orders()->find(7834521);                                       // ?Order

// Statusy i źródła
$statuses = BaseLinker::orders()->getOrderStatusList();    // list<OrderStatus>
$sources = BaseLinker::orders()->getOrderSources();        // list<OrderSource> (type, id, name)
BaseLinker::orders()->setOrderStatus(7834521, 6625);
BaseLinker::orders()->setOrderStatuses([7834521, 7834522], 6625);

// Pola: zmieniane są tylko podane klucze
BaseLinker::orders()->setOrderFields(7834521, ['admin_comments' => 'Zapakować na prezent', 'extra_field_1' => 'VIP']);

// Dodanie zamówienia
$orderId = BaseLinker::orders()->addOrder([
    'order_status_id' => 6624,
    'date_add' => now()->timestamp,
    'currency' => 'PLN',
    'payment_method' => 'Przelew',
    'payment_method_cod' => false,
    'paid' => true,
    'email' => 'jan@example.pl',
    'delivery_method' => 'Kurier',
    'delivery_price' => 15.0,
    'delivery_fullname' => 'Jan Kowalski',
    'delivery_address' => 'Długa 12/4',
    'delivery_postcode' => '00-001',
    'delivery_city' => 'Warszawa',
    'delivery_country_code' => 'PL',
    'want_invoice' => false,
    'products' => [
        ['storage' => 'db', 'storage_id' => 0, 'product_id' => '2847', 'name' => 'Mysz bezprzewodowa', 'sku' => 'LGT-MX-BLK',
         'price_brutto' => 129.99, 'tax_rate' => 23, 'quantity' => 1, 'weight' => 0.12],
    ],
]);
```

Nazwy metod odpowiadają API jeden do jednego (`getOrders`, `addOrderProduct`, `setOrderPayment`, ...).
Identyfikatory są typowanymi argumentami pozycyjnymi, pozostałe pola trafiają do końcowej tablicy z kluczami
z dokumentacji.

## Magazyn (katalog produktów)

```php
$inventories = BaseLinker::inventory()->getInventories();        // list<Inventory>
$warehouses = BaseLinker::warehouses()->getInventoryWarehouses(); // list<Warehouse>, $warehouse->code() === "bl_206"
$groups = BaseLinker::inventory()->getInventoryPriceGroups();     // list<PriceGroup>

// Wszystkie produkty katalogu, po 1000 na stronę, kluczowane id produktu
foreach (BaseLinker::inventory()->iterateProducts(306, ['filter_category_id' => 12]) as $id => $product) {
    $product->sku; $product->name; $product->prices; $product->stock;   // ['bl_206' => 5.0]
}

// Pełne dane (pola tekstowe, zdjęcia, warianty, ...) dla wielu id, w paczkach
foreach (BaseLinker::inventory()->productsData(306, $ids, ['include_suppliers' => true]) as $id => $product) {
    $product->textFields['description'] ?? null;
    foreach ($product->variants as $variantId => $variant) { $variant->sku; $variant->stock; }
}

// Masowe aktualizacje: dowolna liczba produktów, wysyłana w paczkach po 1000, liczniki i ostrzeżenia sumowane
$result = BaseLinker::inventory()->updateStockInChunks(306, [
    2685 => ['bl_206' => 5],
    2686 => ['bl_206' => 3, 'bl_207' => 1],
]);
$result->counter;      // zaktualizowane produkty
$result->warnings;     // ['2686' => 'Warehouse bl_207 ...'], kluczowane id produktu
$result->requests;     // liczba wywołań API

BaseLinker::inventory()->updatePricesInChunks(306, [2685 => ['105' => 20.99]]);

// Dodanie lub aktualizacja produktu (podany product_id = aktualizacja)
BaseLinker::inventory()->addInventoryProduct(306, ['sku' => 'EPL-432', 'prices' => ['105' => 20.99], 'stock' => ['bl_206' => 5],
    'text_fields' => ['name' => 'Harry Potter', 'description' => '...']]);
```

Nie można zapisywać stanów w magazynach utworzonych automatycznie dla sklepów i hurtowni; dla magazynów
zewnętrznych służy `externalStorages()->updateQuantityInChunks()`.

## Kurierzy i etykiety

```php
$couriers = BaseLinker::couriers()->getCouriersList();
$fields = BaseLinker::couriers()->getCourierFields('inpost');            // pola formularza i pola paczek

$package = BaseLinker::couriers()->createPackage(7834521, 'inpost',
    fields: [['id' => 'service', 'value' => 'paczkomaty'], ['id' => 'size', 'value' => 'A']],
    packages: [['weight' => 1.2]],
);
$package->packageId; $package->packageNumber;

// Etykieta jako dane binarne: bez ręcznego dekodowania base64
$label = BaseLinker::couriers()->getLabel('inpost', packageId: $package->packageId);
Storage::put('etykiety/'.$label->filename('etykieta-'.$package->packageId), $label->contents); // pdf, zpl, epl, ...
$label->mimeType();

BaseLinker::couriers()->getOrderPackages(7834521);       // list<Package>, $package->trackingStatus to enum TrackingStatus
BaseLinker::couriers()->getProtocol('dpd', ['package_ids' => [123, 124]]);
BaseLinker::couriers()->createPackageManual(7834521, 'dpd', '0000123456789U', now()->timestamp);
```

## Faktury i paragony

```php
$invoiceId = BaseLinker::invoices()->addInvoice(7834521, seriesId: 15);

foreach (BaseLinker::invoices()->iterate(1, ['series_id' => 15]) as $invoice) {
    $invoice->number; $invoice->totalGross; $invoice->buyer->taxId; $invoice->isCorrection();
}

$file = BaseLinker::invoices()->getInvoiceFile($invoiceId);   // File: contents (binarny PDF), number, filename()
BaseLinker::invoices()->addOrderInvoiceFile($invoiceId, file_get_contents('faktura-erp.pdf'), 'FV 1/11/2026'); // kodowanie po stronie pakietu
```

## Zwroty

```php
foreach (BaseLinker::returns()->iterate(1, ['status_id' => 1001]) as $return) {
    $return->orderId; $return->refunded; $return->products[0]->returnReasonId;
}

BaseLinker::returns()->setOrderReturnStatus(5, 1002);
BaseLinker::returns()->setOrderReturnRefund(5, 129.99, now()->timestamp, ['refund_comment' => 'Zwrócono']);
```

## Odpytywanie dziennika zdarzeń

BaseLinker nie ma webhooków. Zmiany odczytuje się z dziennika zamówień (`getJournalList`, 22 typy zdarzeń)
i dziennika zwrotów (`getOrderReturnJournalList`, 12 typów), które przechowują wpisy przez **3 dni**.

> Dziennik trzeba włączyć w panelu BaseLinker, w ustawieniach API konta. W przeciwnym razie API zwraca pustą
> listę, a komenda wyświetla podpowiedź.

```php
// routes/console.php
Schedule::command('baselinker:poll')->everyMinute()->withoutOverlapping();
// jedno połączenie albo dziennik zwrotów: baselinker:poll hurt --returns
```

```php
use Beziworld\BaseLinker\Enums\JournalEventType;
use Beziworld\BaseLinker\Events\JournalEventReceived;

Event::listen(function (JournalEventReceived $event) {
    if ($event->entry->type === JournalEventType::StatusChanged) {
        // $event->connection, $event->entry->orderId, $event->entry->objectId (id nowego statusu), $event->entry->date
    }
});
```

Id ostatniego przetworzonego wpisu jest zapisywane per połączenie w cache (`journal.cache_store`, użyj trwałego
magazynu), więc każde uruchomienie zaczyna tam, gdzie skończyło poprzednie. Wpisy są dostarczane co najmniej raz,
dlatego listenery powinny być idempotentne. Typy zdarzeń filtrują `journal.order_event_types` /
`journal.return_event_types`, a odpytywane dzienniki wybiera `journal.poll` (domyślnie `['orders']`). Dla tokenów
tenantów wywołaj poller bezpośrednio:
`app(JournalPoller::class)->pollOrders(BaseLinker::using($token, 'tenant-7'))`.

## Limity zapytań

BaseLinker pozwala na 100 zapytań na minutę. Klient liczy zapytania per token w cache Laravela (gdy jeden token
obsługuje kilka serwerów lub workerów, użyj wspólnego magazynu, np. Redis). Po wyczerpaniu limitu czeka na nowe
okno (`BASELINKER_RATE_LIMIT_STRATEGY=wait`, maksymalnie `max_wait` sekund) albo rzuca `RateLimitExceeded`
(`throw`). Zapytania nigdy nie są automatycznie ponawiane: większość metod zapisujących nie jest idempotentna.

## Błędy

API odpowiada na każde wywołanie kodem HTTP 200; błędy przychodzą w kopercie jako `status: ERROR`.

| Wyjątek | Kiedy | Szczegóły |
|---|---|---|
| `ApiException` | `status: ERROR`, błąd HTTP, odpowiedź inna niż JSON, brak połączenia | `errorCode()` (np. `ERROR_BAD_TOKEN`), `errorMessage()`, `apiMethod()`, `httpStatus()`, `isBadToken()` |
| `RateLimitExceeded` | lokalny limiter odrzucił zapytanie | `retryAfter()` w sekundach |
| `InvalidResponse` | metoda plikowa nie zwróciła pliku lub base64 jest niepoprawne | |
| `InvalidConfiguration` | brak tokenu, nieznane połączenie, błędna opcja | |

Wszystkie dziedziczą po `Beziworld\BaseLinker\Exceptions\BaseLinkerException`.

## Pokrycie API

Każda metoda z listy na [api.baselinker.com](https://api.baselinker.com/) (211) ma dedykowaną metodę o tej samej
nazwie w jednym z poniższych zasobów. Metody opublikowane później można wywołać przez
`BaseLinker::call('nazwaMetody', [...])`.

| Zasób | Liczba | Metody API |
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

## Rozwój

```bash
composer install
composer ci      # pint --test, phpstan (poziom 8, strict rules), pest
```

Każda metoda API ma test sprawdzający nagłówek `X-BLToken`, pole `method` i JSON `parameters`.

## Autor

Pakiet tworzy i rozwija **Kamil Ćwiertnia**, [beziworld.eu](https://beziworld.eu).

- E-mail: [biuro@beziworld.eu](mailto:biuro@beziworld.eu)
- Zgłoszenia: [GitHub issues](https://github.com/beziworld/laravel-baselinker/issues)

Potrzebujesz integracji z BaseLinkerem lub marketplace'em? Zapraszam do kontaktu przez [beziworld.eu](https://beziworld.eu).

## Licencja

MIT. Zobacz [LICENSE.md](LICENSE.md). Pakiet nie jest powiązany z BaseLinker ani Base.com.
