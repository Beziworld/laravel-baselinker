<?php

declare(strict_types=1);

namespace Beziworld\BaseLinker\Events;

use Beziworld\BaseLinker\Data\JournalEntry;

/**
 * Dispatched by the journal poller for every new order journal entry (getJournalList).
 *
 * Entries arrive at least once: a listener may see the same entry again
 * after a failure, so handlers should be idempotent.
 */
final readonly class JournalEventReceived
{
    public function __construct(
        public string $connection,
        public JournalEntry $entry,
    ) {}
}
