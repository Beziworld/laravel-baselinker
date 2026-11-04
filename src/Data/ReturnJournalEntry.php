<?php

declare(strict_types=1);

namespace Beziworld\BaseLinker\Data;

use Beziworld\BaseLinker\Enums\ReturnJournalEventType;
use Beziworld\BaseLinker\Support\Cast;
use Carbon\CarbonImmutable;

/**
 * Order return journal entry returned by getOrderReturnJournalList.
 */
final readonly class ReturnJournalEntry
{
    /**
     * @param array<string, mixed> $raw
     */
    public function __construct(
        public int $id,
        public int $returnId,
        public int $typeId,
        public ?ReturnJournalEventType $type,
        public ?int $objectId,
        public ?CarbonImmutable $date,
        public array $raw,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $typeId = Cast::int($data['log_type'] ?? null) ?? 0;
        $objectId = Cast::int($data['object_id'] ?? null);

        return new self(
            id: Cast::int($data['log_id'] ?? $data['id'] ?? null) ?? 0,
            returnId: Cast::int($data['return_id'] ?? null) ?? 0,
            typeId: $typeId,
            type: ReturnJournalEventType::tryFrom($typeId),
            objectId: $objectId !== null && $objectId !== 0 ? $objectId : null,
            date: Cast::date($data['date'] ?? null),
            raw: $data,
        );
    }
}
