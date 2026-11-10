<?php

namespace JundFauz\FilamentKompact\Tests\Concerns;

use BladeUI\Heroicons\BladeHeroiconsServiceProvider;
use BladeUI\Icons\BladeIconsServiceProvider;
use Filament\Actions\ActionsServiceProvider;
use Filament\FilamentServiceProvider;
use Filament\Forms\FormsServiceProvider;
use Filament\Infolists\InfolistsServiceProvider;
use Filament\Notifications\NotificationsServiceProvider;
use Filament\Schemas\SchemasServiceProvider;
use Filament\Support\SupportServiceProvider;
use Filament\Tables\Columns\Column;
use Filament\Tables\TablesServiceProvider;
use Filament\Widgets\WidgetsServiceProvider;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use JundFauz\EloquentKompact\EloquentKompactServiceProvider;
use JundFauz\FilamentKompact\FilamentKompactServiceProvider;
use JundFauz\FilamentKompact\Tests\Fixtures\Category;
use JundFauz\FilamentKompact\Tests\Fixtures\Product;
use JundFauz\FilamentKompact\Tests\Fixtures\User;
use Livewire\LivewireServiceProvider;
use ReflectionProperty;
use Zvizvi\FilamentColumnFilters\FilamentColumnFiltersServiceProvider;

/**
 * The package and its Filament dependencies, shared by the PHPUnit and the Dusk test cases
 */
trait DefinesKompactEnvironment
{
    /**
     * @return array<int, class-string>
     */
    protected function kompactProviders(): array
    {
        // The order of package discovery (by package name): Filament registers its Livewire component hooks,
        // e.g. the one that renders action modals, before Livewire boots and wires the hooks up
        return [
            BladeHeroiconsServiceProvider::class,
            BladeIconsServiceProvider::class,
            ActionsServiceProvider::class,
            FilamentServiceProvider::class,
            FormsServiceProvider::class,
            InfolistsServiceProvider::class,
            NotificationsServiceProvider::class,
            SchemasServiceProvider::class,
            SupportServiceProvider::class,
            TablesServiceProvider::class,
            WidgetsServiceProvider::class,
            EloquentKompactServiceProvider::class,
            LivewireServiceProvider::class,
            FilamentColumnFiltersServiceProvider::class,
            FilamentKompactServiceProvider::class,
        ];
    }

    protected function defineKompactEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:' . base64_encode(str_repeat('a', 32)));
        $app['config']->set('auth.providers.users.model', User::class);
    }

    /**
     * Acts as if the column filter plugin is not registered on a panel
     */
    protected function withoutColumnFilterMacro(): void
    {
        $macros = new ReflectionProperty(Column::class, 'macros');
        $value = $macros->getValue();
        unset($value['columnFilter']);
        $macros->setValue(null, $value);
    }

    protected function createProductsTable(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable();
            $table->string('name');
            $table->string('brand')->nullable();
            $table->boolean('active_is')->default(false);
            $table->decimal('weight_gram', 12, 3)->nullable();
            $table->json('variants_array')->nullable();
            $table->integer('stock_pcs')->nullable();
            $table->decimal('tax_percentage', 5, 2)->nullable();
            $table->string('status_enum')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    protected function createUsersTable(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Apple and Bread (Food, Acme), Cola (Drink, no brand) and a trashed Water (no category)
     *
     * @return array<string, Product>
     */
    protected function seedProducts(): array
    {
        $food = Category::create(['name' => 'Food']);
        $drink = Category::create(['name' => 'Drink']);
        Category::create(['name' => 'Unused']);

        $products = [
            'apple' => Product::create(['name' => 'Apple', 'brand' => 'Acme', 'category_id' => $food->id, 'active_is' => true, 'weight_gram' => '150.5']),
            'bread' => Product::create(['name' => 'Bread', 'brand' => 'Acme', 'category_id' => $food->id, 'weight_gram' => '500']),
            'cola' => Product::create(['name' => 'Cola', 'brand' => '', 'category_id' => $drink->id]),
            'water' => Product::create(['name' => 'Water', 'brand' => null]),
        ];

        $products['water']->delete();

        return $products;
    }
}
