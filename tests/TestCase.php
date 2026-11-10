<?php

namespace JundFauz\FilamentKompact\Tests;

use JundFauz\FilamentKompact\Tests\Concerns\DefinesKompactEnvironment;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Zvizvi\FilamentColumnFilters\FilamentColumnFilters;

abstract class TestCase extends BaseTestCase
{
    use DefinesKompactEnvironment;

    protected function getPackageProviders($app): array
    {
        return $this->kompactProviders();
    }

    protected function setUp(): void
    {
        parent::setUp();

        // The column filter plugin only registers `columnFilter()` when it is added to a panel
        FilamentColumnFilters::registerColumnMacro();
    }

    protected function defineEnvironment($app): void
    {
        $this->defineKompactEnvironment($app);
        $app['config']->set('cache.default', 'array');
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);
    }
}
