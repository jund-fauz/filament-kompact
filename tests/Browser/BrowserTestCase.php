<?php

namespace JundFauz\FilamentKompact\Tests\Browser;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use JundFauz\FilamentKompact\Tests\Concerns\DefinesKompactEnvironment;
use JundFauz\FilamentKompact\Tests\Fixtures\Filament\AdminPanelProvider;
use JundFauz\FilamentKompact\Tests\Fixtures\Product;
use JundFauz\FilamentKompact\Tests\Fixtures\User;
use Orchestra\Testbench\Dusk\Options;
use Orchestra\Testbench\Dusk\TestCase;

/**
 * Runs the Filament panel of the fixtures in Chrome. Local only, see `composer test:browser`.
 *
 * The browser talks to a separate PHP server, so the database is a SQLite file shared by both.
 */
abstract class BrowserTestCase extends TestCase
{
    use DefinesKompactEnvironment;

    protected static bool $assetsPublished = false;

    protected User $user;

    /** @var array<string, Product> */
    protected array $products;

    public static function setUpBeforeClass(): void
    {
        if (! getenv('DUSK_HEADED')) {
            Options::withoutUI();
        }

        Options::windowSize(1600, 1000);

        if (! is_file(static::databasePath())) {
            touch(static::databasePath());
        }

        parent::setUpBeforeClass();
    }

    protected static function databasePath(): string
    {
        return __DIR__ . '/database.sqlite';
    }

    protected function getPackageProviders($app): array
    {
        return [...$this->kompactProviders(), AdminPanelProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $this->defineKompactEnvironment($app);
        $app['config']->set('session.driver', 'file');
        $app['config']->set('cache.default', 'array');
        $app['config']->set('database.default', 'browser');
        $app['config']->set('database.connections.browser', [
            'driver' => 'sqlite',
            'database' => static::databasePath(),
            'foreign_key_constraints' => false,
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        if (! static::$assetsPublished) {
            // The served panel needs the Filament and column filter scripts in the public folder
            Artisan::call('filament:assets');
            static::$assetsPublished = true;
        }

        // Filters, sort and search are kept in the session (KompactTable::$persistInSession)
        File::cleanDirectory(storage_path('framework/sessions'));

        Schema::dropAllTables();
        $this->createUsersTable();
        $this->createProductsTable();
        $this->products = $this->seedProducts();

        $this->user = User::create(['name' => 'Admin', 'email' => 'admin@example.com', 'password' => 'password']);
    }
}
