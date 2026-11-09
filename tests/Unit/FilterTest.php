<?php

namespace JundFauz\FilamentKompact\Tests\Unit;

use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Cache;
use JundFauz\FilamentKompact\Helper\Filter;
use JundFauz\FilamentKompact\Tests\Fixtures\Category;
use JundFauz\FilamentKompact\Tests\Fixtures\Product;
use JundFauz\FilamentKompact\Tests\Fixtures\TableComponent;
use JundFauz\FilamentKompact\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use ReflectionProperty;
use Zvizvi\FilamentColumnFilters\FilamentColumnFilters;
use Zvizvi\FilamentColumnFilters\Filters\ColumnFilter;
use Zvizvi\FilamentColumnFilters\Filters\SelectColumnFilter;

class FilterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createProductsTable();
        Cache::flush();

        $food = Category::create(['name' => 'Food']);
        $drink = Category::create(['name' => 'Drink']);
        Category::create(['name' => 'Unused']);

        Product::create(['name' => 'Apple', 'brand' => 'Acme', 'category_id' => $food->id, 'active_is' => true, 'weight_gram' => '150.5']);
        Product::create(['name' => 'Bread', 'brand' => 'Acme', 'category_id' => $food->id, 'weight_gram' => '500']);
        Product::create(['name' => 'Cola', 'brand' => '', 'category_id' => $drink->id]);
        Product::create(['name' => 'Water', 'brand' => null]);
    }

    protected function tearDown(): void
    {
        (new ReflectionProperty(Filter::class, 'filterConfigurations'))->setValue(null, []);
        (new ReflectionProperty(Filter::class, 'optionQueryResolver'))->setValue(null, null);

        parent::tearDown();
    }

    private function column(string $name): TextColumn
    {
        return TextColumn::make($name)->table(
            Table::make(new TableComponent())->query(Product::query())
        );
    }

    private function filterOptions(Column $column): array
    {
        $filter = FilamentColumnFilters::getColumnFilter($column);
        $this->assertInstanceOf(SelectColumnFilter::class, $filter);

        return $filter->getOptions();
    }

    #[Test]
    public function get_count_counts_the_non_empty_values_of_each_column(): void
    {
        $stats = Filter::getCount(Product::class, 'brand', 'name');

        $this->assertEquals(2, $stats->brand_count);
        $this->assertEquals(4, $stats->name_count);
    }

    #[Test]
    public function get_count_accepts_arrays_of_columns(): void
    {
        $stats = Filter::getCount(Product::class, ['brand'], ['name']);

        $this->assertEquals(2, $stats->brand_count);
        $this->assertEquals(4, $stats->name_count);
    }

    #[Test]
    public function get_count_marks_relational_columns_as_filterable_without_a_query(): void
    {
        $stats = Filter::getCount(Product::class, 'category.name');

        $this->assertInstanceOf(Product::class, $stats);
        $this->assertSame(1, $stats->{'category.name_count'});
    }

    #[Test]
    public function create_for_column_skips_a_column_without_values(): void
    {
        $column = $this->column('brand');

        Filter::createForColumn($column, (object) ['brand_count' => 0]);

        $this->assertNull(FilamentColumnFilters::getColumnFilter($column));
    }

    #[Test]
    public function create_for_column_lists_the_distinct_non_empty_values(): void
    {
        $column = Filter::createForColumn($this->column('brand'), Filter::getCount(Product::class, 'brand'));

        $this->assertSame(['Acme' => 'Acme'], $this->filterOptions($column));
    }

    #[Test]
    public function create_for_column_formats_the_label_by_the_column_token(): void
    {
        $column = Filter::createForColumn($this->column('weight_gram'));

        $this->assertSame(['150.5' => '150.500 gr', '500' => '500.000 gr'], $this->filterOptions($column));
    }

    #[Test]
    public function create_for_column_uses_labels_for_a_boolean_column(): void
    {
        $column = Filter::createForColumn($this->column('active_is'));

        $this->assertSame(['Not active', 'Active'], $this->filterOptions($column));
    }

    #[Test]
    public function create_for_column_lists_the_values_of_a_belongs_to_relation_that_are_in_use(): void
    {
        $column = Filter::createForColumn($this->column('category.name'));

        $options = $this->filterOptions($column);
        ksort($options);

        $this->assertSame(['Drink' => 'Drink', 'Food' => 'Food'], $options);
    }

    #[Test]
    public function create_for_column_can_make_the_column_searchable(): void
    {
        $this->assertFalse(Filter::createForColumn($this->column('brand'))->isSearchable());
        $this->assertTrue(Filter::createForColumn($this->column('brand'), searchable: true)->isSearchable());
    }

    #[Test]
    public function global_and_custom_configurations_run_on_the_filter(): void
    {
        $calls = [];
        Filter::configureUsing(function (ColumnFilter $filter, Column $column) use (&$calls) {
            $calls[] = "global:{$column->getName()}";
        });

        $column = Filter::createForColumn($this->column('brand'), customConfiguration: function (ColumnFilter $filter) use (&$calls) {
            $calls[] = 'custom';
            $filter->multiple();
        });

        $this->assertSame(['global:brand', 'custom'], $calls);
        $this->assertTrue(FilamentColumnFilters::getColumnFilter($column)->isMultiple());
    }

    #[Test]
    public function the_option_query_of_a_relational_column_can_be_overridden(): void
    {
        Filter::resolveOptionQueryUsing(function (&$query) {
            $query = Product::query()->where('name', 'Cola');
        });

        $column = Filter::createForColumn($this->column('category.name'));

        $this->assertSame(['Drink' => 'Drink'], $this->filterOptions($column));
    }

    #[Test]
    public function a_per_column_option_query_runs_after_the_global_one(): void
    {
        Filter::resolveOptionQueryUsing(function (&$query) {
            $query = Product::query()->where('name', 'Cola');
        });

        $column = Filter::createForColumn(
            $this->column('category.name'),
            customFilterOptionConfiguration: function (&$query) {
                $query = Product::query()->where('name', 'Apple');
            },
        );

        $this->assertSame(['Food' => 'Food'], $this->filterOptions($column));
    }
}
