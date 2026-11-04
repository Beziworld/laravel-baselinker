<?php

declare(strict_types=1);

use Beziworld\BaseLinker\Exceptions\RateLimitExceeded;
use Beziworld\BaseLinker\Facades\BaseLinker;
use Beziworld\BaseLinker\Support\TokenRateLimiter;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;

beforeEach(function (): void {
    config()->set('baselinker.rate_limit.enabled', true);
    config()->set('baselinker.rate_limit.max_attempts', 2);
    Http::fake(['*' => Http::response(['status' => 'SUCCESS'])]);
});

it('throws when the budget is used up and the strategy is throw', function (): void {
    config()->set('baselinker.rate_limit.strategy', 'throw');
    $client = BaseLinker::connection();

    $client->call('getInventories');
    $client->call('getInventories');

    try {
        $client->call('getInventories');
        $this->fail('Expected RateLimitExceeded.');
    } catch (RateLimitExceeded $exception) {
        expect($exception->retryAfter())->toBeGreaterThan(0)->toBeLessThanOrEqual(60);
    }

    Http::assertSentCount(2);
});

it('waits for the window to reset when the strategy is wait', function (): void {
    Sleep::fake(syncWithCarbon: true);
    $client = BaseLinker::connection();

    $client->call('getInventories');
    $client->call('getInventories');
    $client->call('getInventories');

    Sleep::assertSleptTimes(1);
    Http::assertSentCount(3);
});

it('throws instead of waiting longer than max_wait', function (): void {
    Sleep::fake();
    config()->set('baselinker.rate_limit.max_wait', 5);
    $client = BaseLinker::connection();

    $client->call('getInventories');
    $client->call('getInventories');

    expect(static fn() => $client->call('getInventories'))->toThrow(RateLimitExceeded::class);
    Sleep::assertNeverSlept();
});

it('shares the budget between clients of the same token', function (): void {
    config()->set('baselinker.rate_limit.strategy', 'throw');

    BaseLinker::connection()->call('getInventories');
    BaseLinker::using(TEST_TOKEN, 'same-token')->call('getInventories');

    expect(app(TokenRateLimiter::class)->remaining(BaseLinker::connection()->config()))->toBe(0)
        ->and(static fn() => BaseLinker::connection()->call('getInventories'))->toThrow(RateLimitExceeded::class);

    BaseLinker::connection('second')->call('getInventories');
    Http::assertSentCount(3);
});

it('does not store the token in the cache key', function (): void {
    expect(TokenRateLimiter::key(TEST_TOKEN))->not->toContain(TEST_TOKEN)->toStartWith('baselinker:rate:');
});
