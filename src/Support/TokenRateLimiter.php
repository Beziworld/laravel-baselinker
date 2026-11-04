<?php

declare(strict_types=1);

namespace Beziworld\BaseLinker\Support;

use Beziworld\BaseLinker\BaseLinkerConfig;
use Beziworld\BaseLinker\Exceptions\RateLimitExceeded;
use Illuminate\Cache\RateLimiter;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Support\Sleep;

/**
 * Client-side limiter of requests per API token, backed by the Laravel cache.
 *
 * The counter key is derived from a hash of the token, so every connection
 * and every runtime token sharing the same token also share the budget, while
 * the token itself never reaches the cache.
 */
final class TokenRateLimiter
{
    /**
     * Upper bound of wait cycles for one request, protecting against a clock that does not advance.
     */
    private const int MAX_WAIT_CYCLES = 5;

    public function __construct(private readonly CacheFactory $cache) {}

    /**
     * Registers one request, waiting or throwing when the budget of the token is used up.
     *
     * @throws RateLimitExceeded When the strategy is "throw" or waiting would exceed max_wait.
     */
    public function acquire(BaseLinkerConfig $config): void
    {
        if (! $config->rateLimitEnabled) {
            return;
        }

        $limiter = new RateLimiter($this->cache->store($config->rateLimitCacheStore));
        $key = self::key($config->token);
        $waited = 0;
        $cycles = 0;

        while ($limiter->tooManyAttempts($key, $config->rateLimitMaxAttempts)) {
            $seconds = max(1, $limiter->availableIn($key));

            if (
                $config->rateLimitStrategy === BaseLinkerConfig::STRATEGY_THROW
                || $waited + $seconds > $config->rateLimitMaxWait
                || ++$cycles > self::MAX_WAIT_CYCLES
            ) {
                throw RateLimitExceeded::after($seconds, $config->rateLimitMaxAttempts, $config->rateLimitDecaySeconds);
            }

            Sleep::for($seconds)->seconds();
            $waited += $seconds;
        }

        $limiter->hit($key, $config->rateLimitDecaySeconds);
    }

    /**
     * Requests left in the current window for the token.
     */
    public function remaining(BaseLinkerConfig $config): int
    {
        $limiter = new RateLimiter($this->cache->store($config->rateLimitCacheStore));

        return $limiter->remaining(self::key($config->token), $config->rateLimitMaxAttempts);
    }

    public static function key(string $token): string
    {
        return 'baselinker:rate:' . hash('sha256', $token);
    }
}
