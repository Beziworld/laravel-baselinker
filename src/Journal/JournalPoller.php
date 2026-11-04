<?php

declare(strict_types=1);

namespace Beziworld\BaseLinker\Journal;

use Beziworld\BaseLinker\BaseLinkerClient;
use Beziworld\BaseLinker\Data\JournalEntry;
use Beziworld\BaseLinker\Data\ReturnJournalEntry;
use Beziworld\BaseLinker\Enums\JournalEventType;
use Beziworld\BaseLinker\Enums\ReturnJournalEventType;
use Beziworld\BaseLinker\Events\JournalEventReceived;
use Beziworld\BaseLinker\Events\ReturnJournalEventReceived;
use Beziworld\BaseLinker\Support\Cast;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Events\Dispatcher;

/**
 * Reads the order and order return journals and dispatches one Laravel event per new entry.
 *
 * The id of the last processed entry is stored per connection in the cache,
 * so every run continues where the previous one stopped. The cursor is
 * advanced after each dispatched event, which gives at-least-once delivery.
 * BaseLinker keeps journal entries for 3 days, so the poller must run more
 * often than that; the journal must also be enabled in the API settings of
 * the account, otherwise the API returns an empty list.
 */
final class JournalPoller
{
    public const string ORDERS = 'orders';

    public const string RETURNS = 'returns';

    public function __construct(
        private readonly CacheFactory $cache,
        private readonly Dispatcher $events,
        private readonly Config $config,
    ) {}

    /**
     * Dispatches JournalEventReceived for every order journal entry newer than the stored cursor.
     *
     * @param list<JournalEventType|int>|null $types Event types to request; the configured list when null, every type when empty.
     * @return int Number of dispatched events.
     */
    public function pollOrders(BaseLinkerClient $client, ?array $types = null): int
    {
        $types = $this->types($types, 'order_event_types');

        return $this->poll(
            $client,
            self::ORDERS,
            fn(int $cursor): array => $client->orders()->getJournalList(self::request($cursor, $types)),
            fn(JournalEntry|ReturnJournalEntry $entry): ?object => $entry instanceof JournalEntry ? new JournalEventReceived($client->name(), $entry) : null,
        );
    }

    /**
     * Dispatches ReturnJournalEventReceived for every order return journal entry newer than the stored cursor.
     *
     * @param list<ReturnJournalEventType|int>|null $types Event types to request; the configured list when null, every type when empty.
     * @return int Number of dispatched events.
     */
    public function pollReturns(BaseLinkerClient $client, ?array $types = null): int
    {
        $types = $this->types($types, 'return_event_types');

        return $this->poll(
            $client,
            self::RETURNS,
            fn(int $cursor): array => $client->returns()->getOrderReturnJournalList(self::request($cursor, $types)),
            fn(JournalEntry|ReturnJournalEntry $entry): ?object => $entry instanceof ReturnJournalEntry ? new ReturnJournalEventReceived($client->name(), $entry) : null,
        );
    }

    /**
     * Id of the last processed entry of a connection; 0 before the first run.
     */
    public function cursor(string $connection, string $journal = self::ORDERS): int
    {
        return Cast::int($this->store()->get($this->key($connection, $journal))) ?? 0;
    }

    /**
     * Moves the cursor, for example to skip history after enabling the journal.
     */
    public function setCursor(string $connection, int $lastLogId, string $journal = self::ORDERS): void
    {
        $this->store()->forever($this->key($connection, $journal), $lastLogId);
    }

    public function resetCursor(string $connection, string $journal = self::ORDERS): void
    {
        $this->store()->forget($this->key($connection, $journal));
    }

    /**
     * @param callable(int): list<JournalEntry|ReturnJournalEntry> $fetch
     * @param callable(JournalEntry|ReturnJournalEntry): ?object $event
     */
    private function poll(BaseLinkerClient $client, string $journal, callable $fetch, callable $event): int
    {
        $cursor = $this->cursor($client->name(), $journal);
        $maxPages = max(1, Cast::int($this->config->get('baselinker.journal.max_pages')) ?? 50);
        $dispatched = 0;

        for ($page = 0; $page < $maxPages; $page++) {
            $entries = array_values(array_filter($fetch($cursor), static fn(JournalEntry|ReturnJournalEntry $entry): bool => $entry->id > $cursor));

            if ($entries === []) {
                break;
            }

            usort($entries, static fn(JournalEntry|ReturnJournalEntry $a, JournalEntry|ReturnJournalEntry $b): int => $a->id <=> $b->id);

            foreach ($entries as $entry) {
                $payload = $event($entry);

                if ($payload !== null) {
                    $this->events->dispatch($payload);
                    $dispatched++;
                }

                $cursor = $entry->id;
                $this->setCursor($client->name(), $cursor, $journal);
            }
        }

        return $dispatched;
    }

    /**
     * @param list<int> $types
     * @return array<string, mixed>
     */
    private static function request(int $cursor, array $types): array
    {
        $parameters = ['last_log_id' => $cursor];

        if ($types !== []) {
            $parameters['logs_types'] = $types;
        }

        return $parameters;
    }

    /**
     * @param list<JournalEventType|ReturnJournalEventType|int>|null $types
     * @return list<int>
     */
    private function types(?array $types, string $configKey): array
    {
        $types ??= Cast::arr($this->config->get('baselinker.journal.' . $configKey));
        $ids = [];

        foreach ($types as $type) {
            $id = $type instanceof JournalEventType || $type instanceof ReturnJournalEventType ? $type->value : Cast::int($type);

            if ($id !== null) {
                $ids[] = $id;
            }
        }

        return $ids;
    }

    private function key(string $connection, string $journal): string
    {
        $prefix = Cast::text($this->config->get('baselinker.journal.cache_prefix')) ?? 'baselinker:journal';

        return $prefix . ':' . $journal . ':' . $connection;
    }

    private function store(): CacheRepository
    {
        return $this->cache->store(Cast::text($this->config->get('baselinker.journal.cache_store')));
    }
}
