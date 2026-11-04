<?php

declare(strict_types=1);

use Beziworld\BaseLinker\Data\BatchResult;
use Beziworld\BaseLinker\Data\Inventory;
use Beziworld\BaseLinker\Data\InventoryProduct;
use Beziworld\BaseLinker\Data\Invoice;
use Beziworld\BaseLinker\Data\JournalEntry;
use Beziworld\BaseLinker\Data\Order;
use Beziworld\BaseLinker\Data\OrderReturn;
use Beziworld\BaseLinker\Data\OrderSource;
use Beziworld\BaseLinker\Data\OrderStatus;
use Beziworld\BaseLinker\Data\Package;
use Beziworld\BaseLinker\Data\PriceGroup;
use Beziworld\BaseLinker\Data\ReturnJournalEntry;
use Beziworld\BaseLinker\Data\Warehouse;
use Beziworld\BaseLinker\Enums\JournalEventType;
use Beziworld\BaseLinker\Enums\ReturnJournalEventType;
use Beziworld\BaseLinker\Enums\TrackingStatus;
use Carbon\CarbonImmutable;

it('maps an order from the documented sample', function (): void {
    $data = [
        'order_id' => 7834521, 'shop_order_id' => 1204, 'external_order_id' => '310298456', 'order_source' => 'allegro',
        'order_source_id' => 1455, 'order_status_id' => 6624, 'date_add' => 1607841161, 'date_confirmed' => 1607841256,
        'date_in_status' => 1607841256, 'confirmed' => true, 'user_login' => 'buyer_nick', 'currency' => 'PLN',
        'payment_method' => 'PayU', 'payment_method_cod' => '0', 'payment_done' => 138.98, 'email' => 'buyer@example.com',
        'phone' => '500100200', 'delivery_method_id' => 14, 'delivery_method' => 'InPost Paczkomaty', 'delivery_price' => 8.99,
        'delivery_package_module' => 'inpost', 'delivery_package_nr' => '620100045678901234', 'delivery_fullname' => 'Jan Kowalski',
        'delivery_company' => '', 'delivery_address' => 'Dluga 12/4', 'delivery_postcode' => '00-001', 'delivery_city' => 'Warszawa',
        'delivery_country' => 'Poland', 'delivery_country_code' => 'PL', 'delivery_point_id' => 'KRA010',
        'delivery_point_name' => 'Paczkomat KRA010', 'delivery_point_address' => 'Krakowska 10', 'delivery_point_postcode' => '30-001',
        'delivery_point_city' => 'Krakow', 'invoice_fullname' => 'Jan Kowalski', 'invoice_nip' => '5260250274', 'want_invoice' => '1',
        'order_page' => 'https://klient.baselinker.com/7834521/a1b2c3d4/', 'pick_state' => 1, 'pack_state' => 1, 'star' => 0,
        'crm_client_id' => 0, 'custom_extra_fields' => ['135' => 'B2B'],
        'products' => [[
            'storage' => 'db', 'storage_id' => 1, 'order_product_id' => 98761234, 'product_id' => '2847', 'variant_id' => '9123',
            'name' => 'Wireless Mouse Logitech MX', 'sku' => 'LGT-MX-BLK', 'ean' => '5901234123457', 'warehouse_id' => 12561,
            'auction_id' => '12345678901', 'price_brutto' => 129.99, 'tax_rate' => 23, 'quantity' => 1, 'weight' => 0.12, 'bundle_id' => 0,
        ]],
    ];

    $order = Order::fromArray($data);

    expect($order->id)->toBe(7834521)
        ->and($order->source)->toBe('allegro')
        ->and($order->dateConfirmed)->toBeInstanceOf(CarbonImmutable::class)
        ->and($order->dateConfirmed?->getTimestamp())->toBe(1607841256)
        ->and($order->payment->cashOnDelivery)->toBeFalse()
        ->and($order->payment->method)->toBe('PayU')
        ->and($order->delivery->method)->toBe('InPost Paczkomaty')
        ->and($order->delivery->point?->id)->toBe('KRA010')
        ->and($order->delivery->address->city)->toBe('Warszawa')
        ->and($order->delivery->address->company)->toBeNull()
        ->and($order->invoiceAddress->taxId)->toBe('5260250274')
        ->and($order->wantInvoice)->toBeTrue()
        ->and($order->products[0]->productId)->toBe('2847')
        ->and($order->products[0]->priceGross)->toBe(129.99)
        ->and($order->products[0]->storageId)->toBe('1')
        ->and($order->totalGross())->toBe(138.98)
        ->and($order->isPaid())->toBeTrue()
        ->and($order->customExtraFields)->toBe(['135' => 'B2B'])
        ->and($order->raw)->toBe($data);
});

it('tolerates sparse order data', function (): void {
    $order = Order::fromArray([]);

    expect($order->id)->toBe(0)->and($order->dateAdd)->toBeNull()->and($order->delivery->point)->toBeNull()->and($order->products)->toBe([]);
});

it('maps catalog products with variants and text fields', function (): void {
    $product = InventoryProduct::fromArray([
        'id' => 2685, 'is_bundle' => false, 'parent_id' => 0, 'sku' => 'EPL-432', 'ean' => '983628103943', 'tags' => ['new'],
        'tax_rate' => 23, 'prices' => ['105' => 20.99], 'stock' => ['bl_206' => 5, 'bl_207' => '2'],
        'text_fields' => ['name' => 'Harry Potter', 'description' => 'Book'],
        'variants' => ['2686' => ['name' => 'Variant name', 'sku' => 'EPL-432-V1', 'prices' => ['105' => 21.99], 'stock' => ['bl_206' => 3]]],
    ]);

    expect($product->id)->toBe(2685)
        ->and($product->name)->toBe('Harry Potter')
        ->and($product->parentId)->toBeNull()
        ->and($product->isVariant())->toBeFalse()
        ->and($product->prices)->toBe(['105' => 20.99])
        ->and($product->stock)->toBe(['bl_206' => 5.0, 'bl_207' => 2.0])
        ->and($product->totalStock())->toBe(7.0)
        ->and($product->variants[2686]->sku)->toBe('EPL-432-V1')
        ->and($product->variants[2686]->id)->toBe(2686)
        ->and($product->variants[2686]->stock)->toBe(['bl_206' => 3.0]);
});

it('maps catalogs, warehouses and price groups', function (): void {
    $inventory = Inventory::fromArray(['inventory_id' => 306, 'name' => 'Default', 'languages' => ['pl', 'en'], 'price_groups' => [105, '106'], 'warehouses' => ['bl_206', 'shop_2445'], 'default_warehouse' => 'bl_206', 'is_default' => true]);
    $warehouse = Warehouse::fromArray(['warehouse_type' => 'bl', 'warehouse_id' => 206, 'name' => 'Main', 'stock_edition' => true]);
    $group = PriceGroup::fromArray(['price_group_id' => 105, 'name' => 'Retail', 'currency' => 'PLN', 'is_default' => true]);

    expect($inventory->priceGroups)->toBe([105, 106])
        ->and($inventory->warehouses)->toBe(['bl_206', 'shop_2445'])
        ->and($inventory->isDefault)->toBeTrue()
        ->and($warehouse->code())->toBe('bl_206')
        ->and($warehouse->stockEdition)->toBeTrue()
        ->and($group->currency)->toBe('PLN');
});

it('maps invoices and recognises corrections', function (): void {
    $invoice = Invoice::fromArray([
        'invoice_id' => 11, 'order_id' => 7, 'type' => 'correcting', 'number' => 'FK 1/2026', 'date_add' => 1767225600,
        'total_price_brutto' => '123.00', 'invoice_nip' => '5260250274', 'invoice_company' => 'ACME', 'correcting_to_invoice_id' => 10,
        'items' => [['name' => 'Item', 'quantity' => 1]],
    ]);

    expect($invoice->isCorrection())->toBeTrue()
        ->and($invoice->totalGross)->toBe(123.0)
        ->and($invoice->buyer->taxId)->toBe('5260250274')
        ->and($invoice->buyer->company)->toBe('ACME')
        ->and($invoice->dateAdd?->toDateString())->toBe('2026-01-01')
        ->and($invoice->items)->toHaveCount(1);
});

it('maps order returns with products', function (): void {
    $return = OrderReturn::fromArray([
        'return_id' => 5, 'order_id' => 7, 'refunded' => '1', 'status_id' => 1001, 'delivery_city' => 'Gdansk',
        'products' => [['order_return_product_id' => 2216, 'order_product_id' => 154904741, 'quantity' => 2, 'return_reason_id' => 1020]],
    ]);

    expect($return->refunded)->toBeTrue()
        ->and($return->deliveryAddress->city)->toBe('Gdansk')
        ->and($return->products[0]->returnReasonId)->toBe(1020)
        ->and($return->products[0]->quantity)->toBe(2);
});

it('maps packages with tracking status', function (): void {
    $package = Package::fromArray(['package_id' => 1, 'courier_package_nr' => 'AB1', 'courier_code' => 'dpd', 'tracking_status' => 5, 'tracking_status_date' => 1767225600, 'is_return' => false]);

    expect($package->trackingStatus)->toBe(TrackingStatus::Delivered)
        ->and($package->trackingStatus?->isFinal())->toBeTrue()
        ->and($package->packageNumber)->toBe('AB1');
});

it('maps journal entries with either id or log_id', function (): void {
    $sample = JournalEntry::fromArray(['log_id' => 456278, 'log_type' => 7, 'order_id' => 8911945, 'object_id' => 5107899, 'date' => 1516369390]);
    $table = JournalEntry::fromArray(['id' => 12, 'log_type' => 99, 'order_id' => 1, 'object_id' => 0, 'date' => 1516369390]);

    expect($sample->id)->toBe(456278)
        ->and($sample->type)->toBe(JournalEventType::InvoiceIssued)
        ->and($sample->type?->objectMeaning())->toBe('invoice id')
        ->and($sample->objectId)->toBe(5107899)
        ->and($table->id)->toBe(12)
        ->and($table->type)->toBeNull()
        ->and($table->typeId)->toBe(99)
        ->and($table->objectId)->toBeNull();
});

it('maps return journal entries', function (): void {
    $entry = ReturnJournalEntry::fromArray(['log_id' => 3, 'log_type' => 11, 'return_id' => 5, 'object_id' => 1001, 'date' => 1516369390]);

    expect($entry->type)->toBe(ReturnJournalEventType::StatusChanged)->and($entry->returnId)->toBe(5);
});

it('covers all 22 order journal event types and 12 return journal event types', function (): void {
    expect(JournalEventType::cases())->toHaveCount(22)
        ->and(ReturnJournalEventType::cases())->toHaveCount(12)
        ->and(TrackingStatus::cases())->toHaveCount(12);
});

it('maps statuses and flattens order sources', function (): void {
    $status = OrderStatus::fromArray(['id' => 6624, 'name' => 'New', 'color' => '#ff0000', 'group_id' => 1, 'is_primary' => 1]);
    $sources = OrderSource::listFromSources(['personal' => ['0' => 'Personal'], 'allegro' => ['1455' => 'Allegro account']]);

    expect($status->isPrimary)->toBeTrue()
        ->and($sources)->toHaveCount(2)
        ->and($sources[1]->type)->toBe('allegro')
        ->and($sources[1]->id)->toBe(1455);
});

it('merges batch results', function (): void {
    $merged = BatchResult::fromArray(['counter' => 2, 'warnings' => ['1' => 'a']])->merge(BatchResult::fromArray(['counter' => 3, 'warnings' => []]));

    expect($merged->counter)->toBe(5)->and($merged->requests)->toBe(2)->and($merged->warnings)->toBe(['1' => 'a']);
});
