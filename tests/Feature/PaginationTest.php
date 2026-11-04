<?php

declare(strict_types=1);

use Beziworld\BaseLinker\Data\InventoryProduct;
use Beziworld\BaseLinker\Data\Order;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

/**
 * @return list<array<string, mixed>>
 */
function orderPage(int $firstId, int $count, int $firstConfirmed): array
{
    $orders = [];

    for ($i = 0; $i < $count; $i++) {
        $orders[] = ['order_id' => $firstId + $i, 'date_confirmed' => $firstConfirmed + $i];
    }

    return $orders;
}

it('iterates orders by confirmation date, moving the cursor one second past the last order', function (): void {
    Http::fake(['*' => Http::sequence()
        ->push(['status' => 'SUCCESS', 'orders' => orderPage(1, 100, 1000)])
        ->push(['status' => 'SUCCESS', 'orders' => orderPage(101, 1, 2000)])]);

    $orders = baseLinkerClient()->orders()->iterate(CarbonImmutable::createFromTimestampUTC(900), ['status_id' => 7]);

    Http::assertNothingSent();

    $all = $orders->all();

    expect($all)->toHaveCount(101)->and($all[100])->toBeInstanceOf(Order::class)->and($all[100]->id)->toBe(101);

    $recorded = Http::recorded();
    expect($recorded)->toHaveCount(2)
        ->and(sentParameters($recorded[0][0]))->toBe(['date_confirmed_from' => 900, 'get_unconfirmed_orders' => false, 'status_id' => 7])
        ->and(sentParameters($recorded[1][0]))->toBe(['date_confirmed_from' => 1100, 'get_unconfirmed_orders' => false, 'status_id' => 7]);
});

it('stops iterating orders after a short page', function (): void {
    Http::fake(['*' => Http::response(['status' => 'SUCCESS', 'orders' => orderPage(1, 3, 1000)])]);

    expect(baseLinkerClient()->orders()->iterate(1000)->count())->toBe(3);
    Http::assertSentCount(1);
});

it('iterates orders by id', function (): void {
    Http::fake(['*' => Http::sequence()
        ->push(['status' => 'SUCCESS', 'orders' => orderPage(5, 100, 0)])
        ->push(['status' => 'SUCCESS', 'orders' => []])]);

    expect(baseLinkerClient()->orders()->iterateById(5, ['get_unconfirmed_orders' => true])->count())->toBe(100);

    $recorded = Http::recorded();
    expect(sentParameters($recorded[1][0]))->toBe(['id_from' => 105, 'get_unconfirmed_orders' => true]);
});

it('finds a single order by id including unconfirmed orders', function (): void {
    Http::fake(['*' => Http::response(['status' => 'SUCCESS', 'orders' => [['order_id' => 42]]])]);

    expect(baseLinkerClient()->orders()->find(42)?->id)->toBe(42);
    Http::assertSent(static fn(Request $request): bool => sentParameters($request) === ['order_id' => 42, 'get_unconfirmed_orders' => true]);
});

it('returns null when an order does not exist', function (): void {
    Http::fake(['*' => Http::response(['status' => 'SUCCESS', 'orders' => []])]);

    expect(baseLinkerClient()->orders()->find(42))->toBeNull();
});

it('iterates invoices by id', function (): void {
    $page = array_map(static fn(int $id): array => ['invoice_id' => $id], range(1, 100));
    Http::fake(['*' => Http::sequence()
        ->push(['status' => 'SUCCESS', 'invoices' => $page])
        ->push(['status' => 'SUCCESS', 'invoices' => [['invoice_id' => 101]]])]);

    $ids = baseLinkerClient()->invoices()->iterate(1, ['series_id' => 3])->map(static fn($invoice): int => $invoice->id)->all();

    expect($ids)->toHaveCount(101)->and(sentParameters(Http::recorded()[1][0]))->toBe(['id_from' => 101, 'series_id' => 3]);
});

it('iterates order returns by id', function (): void {
    Http::fake(['*' => Http::response(['status' => 'SUCCESS', 'returns' => [['return_id' => 9], ['return_id' => 12]]])]);

    $ids = baseLinkerClient()->returns()->iterate(9)->map(static fn($return): int => $return->id)->all();

    expect($ids)->toBe([9, 12]);
    Http::assertSent(static fn(Request $request): bool => sentParameters($request) === ['id_from' => 9]);
});

it('iterates catalog products page by page, keyed by product id', function (): void {
    $full = [];

    for ($id = 1; $id <= 1000; $id++) {
        $full[(string) $id] = ['id' => $id, 'sku' => 'SKU-' . $id];
    }

    Http::fake(['*' => Http::sequence()
        ->push(['status' => 'SUCCESS', 'products' => $full])
        ->push(['status' => 'SUCCESS', 'products' => ['2001' => ['id' => 2001, 'name' => 'Last']]])]);

    $products = baseLinkerClient()->inventory()->iterateProducts(33, ['filter_category_id' => 4])->all();

    expect($products)->toHaveCount(1001)
        ->and($products[2001])->toBeInstanceOf(InventoryProduct::class)
        ->and($products[2001]->name)->toBe('Last')
        ->and(sentParameters(Http::recorded()[0][0]))->toBe(['inventory_id' => 33, 'page' => 1, 'filter_category_id' => 4])
        ->and(sentParameters(Http::recorded()[1][0]))->toBe(['inventory_id' => 33, 'page' => 2, 'filter_category_id' => 4]);
});

it('loads full product data in chunks', function (): void {
    Http::fake(['*' => Http::sequence()
        ->push(['status' => 'SUCCESS', 'products' => ['1' => ['sku' => 'A'], '2' => ['sku' => 'B']]])
        ->push(['status' => 'SUCCESS', 'products' => ['3' => ['sku' => 'C']]])]);

    $products = baseLinkerClient()->inventory()->productsData(33, [1, 2, 3], ['include_suppliers' => true], 2)->all();

    expect(array_keys($products))->toBe([1, 2, 3])
        ->and($products[3]->sku)->toBe('C')
        ->and(sentParameters(Http::recorded()[0][0]))->toBe(['inventory_id' => 33, 'products' => [1, 2], 'include_suppliers' => true])
        ->and(sentParameters(Http::recorded()[1][0]))->toBe(['inventory_id' => 33, 'products' => [3], 'include_suppliers' => true]);
});
