<?php

namespace JundFauz\FilamentKompact\Tests\Unit;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Tables\Columns\Column;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use JundFauz\FilamentKompact\Tests\Fixtures\Product;
use JundFauz\FilamentKompact\Tests\Fixtures\ProductTable;
use JundFauz\FilamentKompact\Tests\Fixtures\TableComponent;
use JundFauz\FilamentKompact\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Zvizvi\FilamentColumnFilters\FilamentColumnFilters;
use Zvizvi\FilamentColumnFilters\Filters\ColumnFilter;

class KompactTableTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createProductsTable();
        Product::create(['name' => 'Apple', 'brand' => null]);

        ProductTable::reset();
    }

    protected function tearDown(): void
    {
        ProductTable::reset();

        parent::tearDown();
    }

    #[Test]
    public function it_persists_filters_sort_and_search_in_the_session_by_default(): void
    {
        $table = $this->table();

        $this->assertTrue($table->persistsFiltersInSession());
        $this->assertTrue($table->persistsSortInSession());
        $this->assertTrue($table->persistsSearchInSession());

        ProductTable::$persist = false;
        $table = $this->table();

        $this->assertFalse($table->persistsFiltersInSession());
        $this->assertFalse($table->persistsSortInSession());
        $this->assertFalse($table->persistsSearchInSession());
    }

    #[Test]
    public function configure_column_filter_runs_on_each_column_filter(): void
    {
        ProductTable::$columnStats = ['name'];
        $columns = [];
        ProductTable::$columnFilterHook = function (ColumnFilter $filter, Column $column) use (&$columns) {
            $columns[] = $column->getName();
            $filter->multiple();
        };

        $table = $this->table();

        $this->assertSame(['name'], $columns);
        $this->assertTrue(FilamentColumnFilters::getColumnFilter($table->getColumns()['name'])->isMultiple());
    }

    #[Test]
    public function get_modified_query_changes_the_query_of_the_table(): void
    {
        Product::create(['name' => 'Bread']);

        $this->assertSame(['Apple', 'Bread'], $this->table()->getQuery()->pluck('name')->all());

        ProductTable::$queryHook = fn ($query) => $query->where('name', 'Bread');

        $this->assertSame(['Bread'], $this->table()->getQuery()->pluck('name')->all());
    }

    #[Test]
    public function the_default_modified_query_leaves_the_query_unchanged(): void
    {
        $query = Product::query();

        $this->assertSame($query, (fn () => static::getModifiedQuery())->bindTo(null, ProductTable::class)()($query));
    }

    private function table(): Table
    {
        return ProductTable::configure(Table::make(new TableComponent())->query(Product::query()));
    }

    /**
     * @return array<int, string>
     */
    private function actionNames(Table $table): array
    {
        return array_map(fn (Action $action) => $action->getName(), array_values($table->getRecordActions()));
    }

    #[Test]
    public function it_adds_the_timestamp_columns_after_the_columns(): void
    {
        $this->assertSame(['name', 'brand', 'created_at', 'updated_at', 'deleted_at'], array_keys($this->table()->getColumns()));
    }

    #[Test]
    public function timestamps_and_deleted_at_can_be_turned_off(): void
    {
        ProductTable::$deletedAt = false;
        $this->assertSame(['name', 'brand', 'created_at', 'updated_at'], array_keys($this->table()->getColumns()));

        ProductTable::$timestamps = false;
        $this->assertSame(['name', 'brand'], array_keys($this->table()->getColumns()));
    }

    #[Test]
    public function it_has_a_trashed_filter_and_bulk_actions(): void
    {
        $table = $this->table();

        $this->assertInstanceOf(TrashedFilter::class, array_values($table->getFilters())[0]);
        $this->assertInstanceOf(BulkActionGroup::class, $table->getToolbarActions()[0]);
    }

    #[Test]
    public function it_has_a_hidden_view_action_and_the_record_actions(): void
    {
        $this->assertSame(['view', 'edit', 'delete', 'forceDelete', 'restore'], $this->actionNames($this->table()));
    }

    #[Test]
    public function the_view_action_can_be_turned_off(): void
    {
        ProductTable::$viewAction = false;

        $this->assertSame(['edit', 'delete', 'forceDelete', 'restore'], $this->actionNames($this->table()));
    }

    #[Test]
    public function record_actions_only_show_an_icon_by_default(): void
    {
        foreach ($this->table()->getRecordActions() as $action) {
            $this->assertTrue($action->isLabelHidden(), $action->getName());
        }

        ProductTable::$iconOnly = false;
        $actions = $this->table()->getRecordActions();

        $this->assertFalse($actions[array_key_last($actions)]->isLabelHidden());
    }

    #[Test]
    public function stats_columns_get_a_column_filter_only_when_they_have_values(): void
    {
        ProductTable::$columnStats = ['name', 'brand'];

        $columns = $this->table()->getColumns();

        $this->assertNotNull(FilamentColumnFilters::getColumnFilter($columns['name']));
        $this->assertNull(FilamentColumnFilters::getColumnFilter($columns['brand']));
    }

    #[Test]
    public function stats_columns_get_no_column_filter_when_the_plugin_is_not_registered(): void
    {
        $this->withoutColumnFilterMacro();
        ProductTable::$columnStats = ['name'];

        $columns = $this->table()->getColumns();

        $this->assertNull(FilamentColumnFilters::getColumnFilter($columns['name']));
        $this->assertNull(FilamentColumnFilters::getColumnFilter($columns['created_at']));
    }

    #[Test]
    public function the_record_actions_follow_the_trashed_state(): void
    {
        $record = Product::first();
        $table = $this->table();

        $visible = fn () => array_values(array_map(
            fn (Action $action) => $action->getName(),
            array_filter($table->getRecordActions(), fn (Action $action) => $action->record($record)->isVisible()),
        ));

        $this->assertSame(['view', 'edit', 'delete'], $visible());

        $record->delete();

        $this->assertSame(['view', 'forceDelete', 'restore'], $visible());
    }
}
