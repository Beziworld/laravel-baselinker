<?php

declare(strict_types=1);

use Beziworld\BaseLinker\BaseLinkerClient;
use Beziworld\BaseLinker\Exceptions\InvalidConfiguration;
use Beziworld\BaseLinker\Facades\BaseLinker;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

it('forwards facade calls to the default connection', function (): void {
    Http::fake(['*' => Http::response(['status' => 'SUCCESS', 'statuses' => [['id' => 5, 'name' => 'New']]])]);

    $statuses = BaseLinker::orders()->getOrderStatusList();

    expect($statuses[0]->name)->toBe('New');
    Http::assertSent(static fn(Request $request): bool => $request->header('X-BLToken') === [TEST_TOKEN]);
});

it('uses the token of a named connection', function (): void {
    Http::fake(['*' => Http::response(['status' => 'SUCCESS'])]);

    BaseLinker::connection('second')->call('getInventories');

    Http::assertSent(static fn(Request $request): bool => $request->header('X-BLToken') === ['second-token']);
    expect(BaseLinker::connection('second')->name())->toBe('second')
        ->and(BaseLinker::connection('second'))->toBe(BaseLinker::connection('second'))
        ->and(BaseLinker::connectionNames())->toBe(['default', 'second']);
});

it('creates clients for runtime tenant tokens', function (): void {
    Http::fake(['*' => Http::response(['status' => 'SUCCESS'])]);

    $client = BaseLinker::using('tenant-token', 'tenant-7', ['timeout' => 5]);
    $client->call('getInventories');

    Http::assertSent(static fn(Request $request): bool => $request->header('X-BLToken') === ['tenant-token']);
    expect($client->name())->toBe('tenant-7')
        ->and($client->config()->timeout)->toBe(5)
        ->and(BaseLinker::using('other-token')->name())->toStartWith('token-');
});

it('resolves the default client from the container', function (): void {
    expect(app(BaseLinkerClient::class)->name())->toBe('default');
});

it('rejects unknown connections', function (): void {
    BaseLinker::connection('missing');
})->throws(InvalidConfiguration::class, 'is not defined');

it('rejects a connection without a token', function (): void {
    config()->set('baselinker.connections.empty', ['token' => null]);

    BaseLinker::connection('empty');
})->throws(InvalidConfiguration::class, 'has no API token');

it('supports a single-account configuration without connections', function (): void {
    config()->set('baselinker.connections', []);
    config()->set('baselinker.token', 'flat-token');
    BaseLinker::purge();

    expect(BaseLinker::connection()->config()->token)->toBe('flat-token');
});

it('validates the rate limit strategy', function (): void {
    config()->set('baselinker.rate_limit.strategy', 'sometimes');

    BaseLinker::connection();
})->throws(InvalidConfiguration::class, 'rate_limit.strategy');

it('fails for unknown client methods', function (): void {
    BaseLinker::doesNotExist();
})->throws(BadMethodCallException::class);
