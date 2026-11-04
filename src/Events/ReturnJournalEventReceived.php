<?php

declare(strict_types=1);

namespace Beziworld\BaseLinker\Events;

use Beziworld\BaseLinker\Data\ReturnJournalEntry;

/**
 * Dispatched by the journal poller for every new order return journal entry (getOrderReturnJournalList).
 *
 * Entries arrive at least once, so handlers should be idempotent.
 */
final readonly class ReturnJournalEventReceived
{
    public function __construct(
        public string $connection,
        public ReturnJournalEntry $entry,
    ) {}
}
