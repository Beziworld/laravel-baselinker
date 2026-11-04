<?php

declare(strict_types=1);

namespace Beziworld\BaseLinker;

use Beziworld\BaseLinker\Exceptions\InvalidConfiguration;
use Beziworld\BaseLinker\Support\Cast;

/**
 * Settings of a single BaseLinker connection (one API token).
 */
final readonly class BaseLinkerConfig
{
    public const string DEFAULT_URL = 'https://api.baselinker.com/connector.php';

    public const string STRATEGY_WAIT = 'wait';

    public const string STRATEGY_THROW = 'throw';

    public function __construct(
        public string $token,
        public string $url = self::DEFAULT_URL,
        public int $timeout = 30,
        public int $connectTimeout = 10,
        public bool $rateLimitEnabled = true,
        public int $rateLimitMaxAttempts = 100,
        public int $rateLimitDecaySeconds = 60,
        public string $rateLimitStrategy = self::STRATEGY_WAIT,
        public int $rateLimitMaxWait = 60,
        public ?string $rateLimitCacheStore = null,
    ) {}

    /**
     * Builds the configuration from a connection array merged with the shared options.
     *
     * @param array<array-key, mixed> $config
     *
     * @throws InvalidConfiguration When the token is missing or the strategy is unknown.
     */
    public static function fromArray(array $config, string $connection = 'default'): self
    {
        $token = Cast::text($config['token'] ?? null);

        if ($token === null) {
            throw InvalidConfiguration::missingToken($connection);
        }

        $limit = Cast::arr($config['rate_limit'] ?? null);
        $strategy = Cast::text($limit['strategy'] ?? null) ?? self::STRATEGY_WAIT;

        if (! in_array($strategy, [self::STRATEGY_WAIT, self::STRATEGY_THROW], true)) {
            throw InvalidConfiguration::invalidValue('rate_limit.strategy', '"wait" or "throw"');
        }

        return new self(
            token: $token,
            url: Cast::text($config['url'] ?? null) ?? self::DEFAULT_URL,
            timeout: max(1, Cast::int($config['timeout'] ?? null) ?? 30),
            connectTimeout: max(1, Cast::int($config['connect_timeout'] ?? null) ?? 10),
            rateLimitEnabled: Cast::bool($limit['enabled'] ?? null) ?? true,
            rateLimitMaxAttempts: max(1, Cast::int($limit['max_attempts'] ?? null) ?? 100),
            rateLimitDecaySeconds: max(1, Cast::int($limit['decay_seconds'] ?? null) ?? 60),
            rateLimitStrategy: $strategy,
            rateLimitMaxWait: max(0, Cast::int($limit['max_wait'] ?? null) ?? 60),
            rateLimitCacheStore: Cast::text($limit['cache_store'] ?? null),
        );
    }
}
