<?php

declare(strict_types=1);

namespace Beziworld\BaseLinker;

use Beziworld\BaseLinker\Exceptions\InvalidConfiguration;
use Beziworld\BaseLinker\Support\TokenRateLimiter;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Http\Client\Factory as HttpFactory;

/**
 * Resolves BaseLinker clients for any number of accounts.
 *
 * Named connections (one API token each) are defined in config/baselinker.php,
 * in the same way as database connections; options outside "connections"
 * are shared defaults. Clients for tokens stored at runtime (for example per
 * tenant in the database) are created with using(). Calls made directly on
 * the manager go to the default connection.
 *
 * @mixin BaseLinkerClient
 */
final class BaseLinkerManager
{
    /** @var array<string, BaseLinkerClient> */
    private array $clients = [];

    public function __construct(
        private readonly HttpFactory $http,
        private readonly Config $config,
        private readonly ?TokenRateLimiter $limiter = null,
    ) {}

    /**
     * Client of a configured connection; the default connection when no name is given.
     *
     * @throws InvalidConfiguration When the connection is not defined or has no token.
     */
    public function connection(?string $name = null): BaseLinkerClient
    {
        $name ??= $this->getDefaultConnection();

        return $this->clients[$name] ??= new BaseLinkerClient($this->http, $this->configFor($name), $this->limiter, $name);
    }

    /**
     * Client for a token provided at runtime, merged with the shared options.
     *
     * The name identifies the account in journal cursors and events; by
     * default it is derived from a hash of the token.
     *
     * @param array<string, mixed> $options Connection options overriding the shared ones (url, timeout, rate_limit, ...).
     */
    public function using(string $token, ?string $name = null, array $options = []): BaseLinkerClient
    {
        $config = BaseLinkerConfig::fromArray(self::merge($this->shared(), ['token' => $token] + $options), $name ?? 'runtime');

        return new BaseLinkerClient($this->http, $config, $this->limiter, $name ?? 'token-' . substr(hash('sha256', $token), 0, 12));
    }

    public function getDefaultConnection(): string
    {
        $default = $this->config->get('baselinker.default');

        return is_string($default) && $default !== '' ? $default : 'default';
    }

    /**
     * Names of the configured connections.
     *
     * @return list<string>
     */
    public function connectionNames(): array
    {
        $connections = $this->config->get('baselinker.connections');

        if (! is_array($connections) || $connections === []) {
            return [$this->getDefaultConnection()];
        }

        return array_map(strval(...), array_keys($connections));
    }

    /**
     * Forgets resolved clients, for example after the configuration changed.
     */
    public function purge(?string $name = null): void
    {
        if ($name === null) {
            $this->clients = [];

            return;
        }

        unset($this->clients[$name]);
    }

    /**
     * @param array<array-key, mixed> $arguments
     */
    public function __call(string $method, array $arguments): mixed
    {
        $callable = [$this->connection(), $method];

        if (! is_callable($callable)) {
            throw new \BadMethodCallException(sprintf('Method %s::%s does not exist.', BaseLinkerClient::class, $method));
        }

        return $callable(...$arguments);
    }

    private function configFor(string $name): BaseLinkerConfig
    {
        $connections = $this->config->get('baselinker.connections');

        if (! is_array($connections) || $connections === []) {
            // Single-account configuration with the token at the top level.
            if ($name !== $this->getDefaultConnection()) {
                throw InvalidConfiguration::unknownConnection($name);
            }

            return BaseLinkerConfig::fromArray($this->shared(), $name);
        }

        $connection = $connections[$name] ?? null;

        if (! is_array($connection)) {
            throw InvalidConfiguration::unknownConnection($name);
        }

        return BaseLinkerConfig::fromArray(self::merge($this->shared(), $connection), $name);
    }

    /**
     * Applies connection options over the shared ones; empty values (unset environment variables) do not override.
     *
     * Nested arrays (rate_limit) are merged key by key.
     *
     * @param array<string, mixed> $shared
     * @param array<array-key, mixed> $connection
     * @return array<string, mixed>
     */
    private static function merge(array $shared, array $connection): array
    {
        foreach ($connection as $key => $item) {
            $key = (string) $key;

            if (is_array($item) && is_array($shared[$key] ?? null)) {
                /** @var array<string, mixed> $nested */
                $nested = $shared[$key];
                $shared[$key] = self::merge($nested, $item);
            } elseif ($item !== null && $item !== '') {
                $shared[$key] = $item;
            }
        }

        return $shared;
    }

    /**
     * Options shared by every connection: everything outside "default", "connections" and "journal".
     *
     * @return array<string, mixed>
     */
    private function shared(): array
    {
        $config = $this->config->get('baselinker', []);
        $config = is_array($config) ? $config : [];
        unset($config['default'], $config['connections'], $config['journal']);

        /** @var array<string, mixed> $config */
        return $config;
    }
}
