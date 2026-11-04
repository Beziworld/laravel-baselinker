<?php

declare(strict_types=1);

use Beziworld\BaseLinker\Events\JournalEventReceived;
use Beziworld\BaseLinker\Events\ReturnJournalEventReceived;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    Event::fake([JournalEventReceived::class, ReturnJournalEventReceived::class]);
});

it('polls the order journal of the given connection', function (): void {
    Http::fake(['*' => Http::sequence()
        ->push(['status' => 'SUCCESS', 'logs' => [['log_id' => 1, 'log_type' => 1, 'order_id' => 9, 'object_id' => 0, 'date' => 1]]])
        ->push(['status' => 'SUCCESS', 'logs' => []])]);

    $this->artisan('baselinker:poll', ['connection' => 'second'])
        ->expectsOutputToContain('[second] order journal: 1 new event(s).')
        ->assertSuccessful();

    Http::assertSent(static fn(Request $request): bool => $request->header('X-BLToken') === ['second-token']);
    Event::assertDispatched(JournalEventReceived::class, static fn(JournalEventReceived $event): bool => $event->connection === 'second');
});

it('polls every configured connection and hints when the journal is empty', function (): void {
    Http::fake(['*' => Http::response(['status' => 'SUCCESS', 'logs' => []])]);

    $this->artisan('baselinker:poll')
        ->expectsOutputToContain('[default] order journal: 0 new event(s).')
        ->expectsOutputToContain('[second] order journal: 0 new event(s).')
        ->expectsOutputToContain('enabled in the API settings')
        ->assertSuccessful();

    Http::assertSentCount(2);
});

it('polls the return journal with --returns', function (): void {
    Http::fake(['*' => Http::response(['status' => 'SUCCESS', 'logs' => []])]);

    $this->artisan('baselinker:poll', ['connection' => 'default', '--returns' => true])
        ->expectsOutputToContain('[default] return journal: 0 new event(s).')
        ->assertSuccessful();

    Http::assertSent(static fn(Request $request): bool => ($request->data()['method'] ?? null) === 'getOrderReturnJournalList');
    Http::assertNotSent(static fn(Request $request): bool => ($request->data()['method'] ?? null) === 'getJournalList');
});

it('reports API errors and exits with failure', function (): void {
    Http::fake(['*' => Http::response(['status' => 'ERROR', 'error_code' => 'ERROR_BAD_TOKEN', 'error_message' => 'Invalid user token'])]);

    $this->artisan('baselinker:poll', ['connection' => 'default'])
        ->expectsOutputToContain('ERROR_BAD_TOKEN')
        ->assertFailed();
});
