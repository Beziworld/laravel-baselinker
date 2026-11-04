<?php

declare(strict_types=1);

use Beziworld\BaseLinker\Exceptions\ApiException;
use Illuminate\Support\Facades\Http;

it('updates stock in chunks of 1000 and sums counters and warnings', function (): void {
    Http::fake(['*' => Http::sequence()
        ->push(['status' => 'SUCCESS', 'counter' => 1000, 'warnings' => ['7' => 'Product does not exist']])
        ->push(['status' => 'SUCCESS', 'counter' => 999, 'warnings' => ['1500' => 'Warehouse is read-only']])
        ->push(['status' => 'SUCCESS', 'counter' => 500, 'warnings' => []])]);

    $stock = [];

    for ($id = 1; $id <= 2500; $id++) {
        $stock[$id] = ['bl_1' => $id % 10];
    }

    $result = baseLinkerClient()->inventory()->updateStockInChunks(5, $stock);

    expect($result->counter)->toBe(2499)
        ->and($result->requests)->toBe(3)
        ->and($result->warnings)->toBe(['7' => 'Product does not exist', '1500' => 'Warehouse is read-only'])
        ->and($result->hasWarnings())->toBeTrue();

    $recorded = Http::recorded();
    $first = sentParameters($recorded[0][0]);
    $last = sentParameters($recorded[2][0]);

    expect($recorded)->toHaveCount(3)
        ->and($first['inventory_id'])->toBe(5)
        ->and($first['products'])->toHaveCount(1000)
        ->and(array_key_first($last['products']))->toBe(2001)
        ->and($last['products'][2500])->toBe(['bl_1' => 0]);
});

it('updates prices in chunks keeping product ids as object keys', function (): void {
    Http::fake(['*' => Http::response(['status' => 'SUCCESS', 'counter' => 1, 'warnings' => []])]);

    $result = baseLinkerClient()->inventory()->updatePricesInChunks(5, [0 => ['105' => 10.5], 1 => ['105' => 11.0]], 1);

    expect($result->counter)->toBe(2)->and($result->requests)->toBe(2);
    expect((string) Http::recorded()[1][0]->data()['parameters'])->toBe('{"inventory_id":5,"products":{"1":{"105":11.0}}}');
});

it('updates external storage quantity in chunks of lists', function (): void {
    Http::fake(['*' => Http::response(['status' => 'SUCCESS', 'counter' => 2, 'warnings' => []])]);

    $products = array_map(static fn(int $id): array => [$id, 0, 5], range(1, 1500));
    $result = baseLinkerClient()->externalStorages()->updateQuantityInChunks('shop_2445', $products);

    $second = sentParameters(Http::recorded()[1][0]);

    expect($result->counter)->toBe(4)
        ->and($second['storage_id'])->toBe('shop_2445')
        ->and(array_is_list($second['products']))->toBeTrue()
        ->and($second['products'][0])->toBe([1001, 0, 5]);
});

it('returns an empty result for an empty batch without calling the API', function (): void {
    Http::fake();

    $result = baseLinkerClient()->inventory()->updateStockInChunks(5, []);

    expect($result->counter)->toBe(0)->and($result->requests)->toBe(0);
    Http::assertNothingSent();
});

it('stops at the first failing chunk', function (): void {
    Http::fake(['*' => Http::sequence()
        ->push(['status' => 'SUCCESS', 'counter' => 1, 'warnings' => []])
        ->push(['status' => 'ERROR', 'error_code' => 'ERROR_STORAGE', 'error_message' => 'Locked'])]);

    expect(static fn() => baseLinkerClient()->inventory()->updateStockInChunks(5, [1 => ['bl_1' => 1], 2 => ['bl_1' => 1], 3 => ['bl_1' => 1]], 1))
        ->toThrow(ApiException::class, 'Locked');

    Http::assertSentCount(2);
});
