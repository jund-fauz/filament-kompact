<?php

namespace JundFauz\FilamentKompact\Tests;

use Filament\Facades\Filament;
use JundFauz\FilamentKompact\Tests\Fixtures\Filament\AdminPanelProvider;
use JundFauz\FilamentKompact\Tests\Fixtures\Product;
use JundFauz\FilamentKompact\Tests\Fixtures\User;

/**
 * Boots a Filament panel with {@see \JundFauz\FilamentKompact\Tests\Fixtures\Filament\ProductResource},
 * which uses every macro and helper of the package
 */
abstract class PanelTestCase extends TestCase
{
    protected User $user;

    /** @var array<string, Product> */
    protected array $products;

    protected function getPackageProviders($app): array
    {
        return [...parent::getPackageProviders($app), AdminPanelProvider::class];
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->createUsersTable();
        $this->createProductsTable();
        $this->products = $this->seedProducts();

        $this->user = User::create(['name' => 'Admin', 'email' => 'admin@example.com', 'password' => 'password']);
        $this->actingAs($this->user);

        Filament::setCurrentPanel('admin');
    }
}
