<?php

declare(strict_types=1);

namespace Beziworld\BaseLinker\Exceptions;

/**
 * Raised by the client-side limiter when the per-token request budget is used up
 * and waiting is disabled or would take longer than allowed.
 */
final class RateLimitExceeded extends BaseLinkerException
{
    public function __construct(string $message, private readonly int $retryAfter)
    {
        parent::__construct($message);
    }

    public static function after(int $seconds, int $maxAttempts, int $decaySeconds): self
    {
        return new self(
            sprintf('BaseLinker rate limit of %d requests per %d seconds reached; retry in %d seconds.', $maxAttempts, $decaySeconds, $seconds),
            $seconds,
        );
    }

    /**
     * Seconds until the limiter accepts the next request.
     */
    public function retryAfter(): int
    {
        return $this->retryAfter;
    }
}
