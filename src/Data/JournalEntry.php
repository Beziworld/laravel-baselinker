<?php

declare(strict_types=1);

namespace Beziworld\BaseLinker\Data;

use Beziworld\BaseLinker\Enums\JournalEventType;
use Beziworld\BaseLinker\Support\Cast;
use Carbon\CarbonImmutable;

/**
 * Order journal entry returned by getJournalList.
 *
 * The documentation names the identifier "id" in the field table and
 * "log_id" in the sample response; both are accepted.
 */
final readonly class JournalEntry
{
    /**
     * @param array<string, mixed> $raw
     */
    public function __construct(
        public int $id,
        public int $orderId,
        public int $typeId,
        public ?JournalEventType $type,
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
            orderId: Cast::int($data['order_id'] ?? null) ?? 0,
            typeId: $typeId,
            type: JournalEventType::tryFrom($typeId),
            objectId: $objectId !== null && $objectId !== 0 ? $objectId : null,
            date: Cast::date($data['date'] ?? null),
            raw: $data,
        );
    }
}
