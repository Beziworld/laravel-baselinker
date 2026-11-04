<?php

declare(strict_types=1);

namespace Beziworld\BaseLinker\Tests;

use Beziworld\BaseLinker\BaseLinkerServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [BaseLinkerServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('cache.default', 'array');
        $app['config']->set('baselinker.connections', [
            'default' => ['token' => TEST_TOKEN],
            'second' => ['token' => 'second-token'],
        ]);
        $app['config']->set('baselinker.rate_limit.enabled', false);
    }
}
