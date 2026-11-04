<?php

declare(strict_types=1);

namespace Beziworld\BaseLinker;

use Beziworld\BaseLinker\Commands\PollJournalCommand;
use Beziworld\BaseLinker\Journal\JournalPoller;
use Beziworld\BaseLinker\Support\TokenRateLimiter;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\ServiceProvider;

/**
 * Registers the connection manager, the default client, the journal poller and the polling command.
 */
final class BaseLinkerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/baselinker.php', 'baselinker');

        $this->app->singleton(TokenRateLimiter::class, static fn(Application $app): TokenRateLimiter => new TokenRateLimiter(
            $app->make(CacheFactory::class),
        ));

        $this->app->singleton(BaseLinkerManager::class, static fn(Application $app): BaseLinkerManager => new BaseLinkerManager(
            $app->make(HttpFactory::class),
            $app->make(Config::class),
            $app->make(TokenRateLimiter::class),
        ));

        $this->app->bind(BaseLinkerClient::class, static fn(Application $app): BaseLinkerClient => $app->make(BaseLinkerManager::class)->connection());

        $this->app->singleton(JournalPoller::class, static fn(Application $app): JournalPoller => new JournalPoller(
            $app->make(CacheFactory::class),
            $app->make(Dispatcher::class),
            $app->make(Config::class),
        ));
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([PollJournalCommand::class]);
            $this->publishes([__DIR__ . '/../config/baselinker.php' => $this->app->configPath('baselinker.php')], 'baselinker-config');
        }
    }
}
