<?php

declare(strict_types=1);

use Beziworld\BaseLinker\Enums\JournalEventType;
use Beziworld\BaseLinker\Events\JournalEventReceived;
use Beziworld\BaseLinker\Events\ReturnJournalEventReceived;
use Beziworld\BaseLinker\Journal\JournalPoller;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    Event::fake([JournalEventReceived::class, ReturnJournalEventReceived::class]);
});

it('dispatches an event per new order journal entry and stores the cursor', function (): void {
    Http::fake(['*' => Http::sequence()
        ->push(['status' => 'SUCCESS', 'logs' => [
            ['log_id' => 11, 'log_type' => 18, 'order_id' => 100, 'object_id' => 6624, 'date' => 1767225600],
            ['log_id' => 10, 'log_type' => 1, 'order_id' => 100, 'object_id' => 0, 'date' => 1767225500],
        ]])
        ->push(['status' => 'SUCCESS', 'logs' => []])]);

    $poller = app(JournalPoller::class);
    $count = $poller->pollOrders(baseLinkerClient());

    expect($count)->toBe(2)->and($poller->cursor('default'))->toBe(11);

    $ids = [];
    Event::assertDispatched(JournalEventReceived::class, static function (JournalEventReceived $event) use (&$ids): bool {
        $ids[] = $event->entry->id;

        return $event->connection === 'default';
    });
    expect($ids)->toBe([10, 11]);

    Event::assertDispatched(JournalEventReceived::class, static fn(JournalEventReceived $event): bool => $event->entry->type === JournalEventType::StatusChanged
        && $event->entry->objectId === 6624);

    expect(sentParameters(Http::recorded()[0][0]))->toBe(['last_log_id' => 0])
        ->and(sentParameters(Http::recorded()[1][0]))->toBe(['last_log_id' => 11]);
});

it('skips entries that were already processed', function (): void {
    Http::fake(['*' => Http::sequence()
        ->push(['status' => 'SUCCESS', 'logs' => [
            ['log_id' => 11, 'log_type' => 18, 'order_id' => 100, 'object_id' => 1, 'date' => 1767225600],
            ['log_id' => 12, 'log_type' => 3, 'order_id' => 100, 'object_id' => 0, 'date' => 1767225700],
        ]])
        ->push(['status' => 'SUCCESS', 'logs' => [['log_id' => 12, 'log_type' => 3, 'order_id' => 100, 'object_id' => 0, 'date' => 1767225700]]])]);

    $poller = app(JournalPoller::class);
    $poller->setCursor('default', 11);

    expect($poller->pollOrders(baseLinkerClient()))->toBe(1)->and($poller->cursor('default'))->toBe(12);
    Event::assertDispatchedTimes(JournalEventReceived::class, 1);
    Http::assertSentCount(2);
});

it('requests only the configured event types', function (): void {
    config()->set('baselinker.journal.order_event_types', [18, 3]);
    Http::fake(['*' => Http::response(['status' => 'SUCCESS', 'logs' => []])]);

    app(JournalPoller::class)->pollOrders(baseLinkerClient());

    Http::assertSent(static fn(Request $request): bool => sentParameters($request) === ['last_log_id' => 0, 'logs_types' => [18, 3]]);
});

it('accepts event type enums passed explicitly', function (): void {
    Http::fake(['*' => Http::response(['status' => 'SUCCESS', 'logs' => []])]);

    app(JournalPoller::class)->pollOrders(baseLinkerClient(), [JournalEventType::PackageCreated, JournalEventType::PackageStatusChanged]);

    Http::assertSent(static fn(Request $request): bool => sentParameters($request) === ['last_log_id' => 0, 'logs_types' => [9, 22]]);
});

it('keeps separate cursors per connection and journal', function (): void {
    Http::fake(['*' => Http::sequence()
        ->push(['status' => 'SUCCESS', 'logs' => [['log_id' => 50, 'log_type' => 1, 'order_id' => 1, 'object_id' => 0, 'date' => 1]]])
        ->push(['status' => 'SUCCESS', 'logs' => []])]);

    $poller = app(JournalPoller::class);
    $poller->pollOrders(baseLinkerClient('second'));

    expect($poller->cursor('second'))->toBe(50)
        ->and($poller->cursor('default'))->toBe(0)
        ->and($poller->cursor('second', JournalPoller::RETURNS))->toBe(0);

    $poller->resetCursor('second');
    expect($poller->cursor('second'))->toBe(0);
});

it('polls the order return journal', function (): void {
    Http::fake(['*' => Http::sequence()
        ->push(['status' => 'SUCCESS', 'logs' => [['log_id' => 7, 'log_type' => 5, 'return_id' => 3, 'object_id' => 0, 'date' => 1767225600]]])
        ->push(['status' => 'SUCCESS', 'logs' => []])]);

    $poller = app(JournalPoller::class);

    expect($poller->pollReturns(baseLinkerClient()))->toBe(1)->and($poller->cursor('default', JournalPoller::RETURNS))->toBe(7);
    Event::assertDispatched(ReturnJournalEventReceived::class, static fn(ReturnJournalEventReceived $event): bool => $event->entry->returnId === 3);
    Http::assertSent(static fn(Request $request): bool => ($request->data()['method'] ?? null) === 'getOrderReturnJournalList');
});

it('limits the number of pages per run', function (): void {
    config()->set('baselinker.journal.max_pages', 1);
    Http::fake(['*' => Http::response(['status' => 'SUCCESS', 'logs' => [['log_id' => 1, 'log_type' => 1, 'order_id' => 1, 'object_id' => 0, 'date' => 1]]])]);

    app(JournalPoller::class)->pollOrders(baseLinkerClient());

    Http::assertSentCount(1);
});
