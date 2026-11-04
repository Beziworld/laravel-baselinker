<?php

declare(strict_types=1);

namespace Beziworld\BaseLinker\Data;

use Beziworld\BaseLinker\Support\Cast;

/**
 * Outcome of a bulk stock, price or quantity update: number of updated items and per-item warnings.
 */
final readonly class BatchResult
{
    /**
     * @param array<array-key, mixed> $warnings Warnings keyed by product id, as returned by the API.
     * @param int $requests Number of API calls the result was assembled from.
     */
    public function __construct(
        public int $counter,
        public array $warnings,
        public int $requests = 1,
    ) {}

    /**
     * @param array<array-key, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(Cast::int($data['counter'] ?? null) ?? 0, Cast::arr($data['warnings'] ?? null));
    }

    public static function empty(): self
    {
        return new self(0, [], 0);
    }

    /**
     * Combines the result of another chunk with this one; warnings keep their product id keys.
     */
    public function merge(self $other): self
    {
        return new self($this->counter + $other->counter, array_replace($this->warnings, $other->warnings), $this->requests + $other->requests);
    }

    public function hasWarnings(): bool
    {
        return $this->warnings !== [];
    }
}
