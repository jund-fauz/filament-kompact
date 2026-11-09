<?php

namespace JundFauz\FilamentKompact\Tests\Unit;

use Filament\Forms\Components\Repeater;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\TextColumn;
use JundFauz\FilamentKompact\FilamentKompactServiceProvider;
use JundFauz\FilamentKompact\Macros;
use JundFauz\FilamentKompact\Table\TextColumnMacros;
use JundFauz\FilamentKompact\Tests\Fixtures\TableComponent;
use JundFauz\FilamentKompact\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Zvizvi\FilamentColumnFilters\FilamentColumnFilters;
use Zvizvi\FilamentColumnFilters\Filters\SelectColumnFilter;

class MacrosTest extends TestCase
{
    #[Test]
    public function the_service_provider_registers_every_macro(): void
    {
        $this->assertTrue(TextColumn::hasMacro('formatId'));
        $this->assertTrue(TextColumn::hasMacro('searchSortable'));
        $this->assertTrue(TextEntry::hasMacro('formatId'));
        $this->assertTrue(Repeater::hasMacro('minOneItem'));
        $this->assertTrue(Column::hasMacro('createFilter'));
    }

    #[Test]
    public function only_public_static_methods_declared_on_the_macro_class_are_called(): void
    {
        TextColumn::flushMacros();

        (new FilamentKompactServiceProvider($this->app))->applyMacros();

        $this->assertTrue(TextColumn::hasMacro('formatId'));
        $this->assertTrue(TextColumn::hasMacro('searchSortable'));
        $this->assertContains(TextColumnMacros::class, Macros::lists);
    }

    #[Test]
    public function text_column_format_id_pads_the_state_and_adds_the_prefix(): void
    {
        $this->assertSame('INV-005', TextColumn::make('id')->formatId('INV-')->formatState('5'));
        $this->assertSame('P-00042', TextColumn::make('id')->formatId('P-', 5)->formatState('42'));
        $this->assertSame('X-1234', TextColumn::make('id')->formatId('X-')->formatState('1234'));
    }

    #[Test]
    public function text_entry_format_id_pads_the_state_and_adds_the_prefix(): void
    {
        $entry = TextEntry::make('id')->formatId('INV-');
        $entry->container(Schema::make(new TableComponent()));

        $this->assertSame('INV-007', $entry->formatState('7'));
    }

    #[Test]
    public function search_sortable_makes_the_column_searchable_and_sortable(): void
    {
        $column = TextColumn::make('name')->searchSortable();

        $this->assertTrue($column->isSearchable());
        $this->assertTrue($column->isSortable());
    }

    #[Test]
    public function search_sortable_accepts_separate_conditions(): void
    {
        $column = TextColumn::make('name')->searchSortable(sortableCondition: false);

        $this->assertTrue($column->isSearchable());
        $this->assertFalse($column->isSortable());

        $column = TextColumn::make('name')->searchSortable(searchableCondition: false);

        $this->assertFalse($column->isSearchable());
        $this->assertTrue($column->isSortable());
    }

    #[Test]
    public function repeater_min_one_item_requires_one_item(): void
    {
        $repeater = Repeater::make('items')->minOneItem();

        $this->assertSame(1, $repeater->getMinItems());
    }

    #[Test]
    public function column_create_filter_attaches_a_select_column_filter(): void
    {
        $column = TextColumn::make('name')->createFilter();

        $this->assertInstanceOf(SelectColumnFilter::class, FilamentColumnFilters::getColumnFilter($column));
    }

    #[Test]
    public function column_create_filter_skips_a_column_with_an_empty_count(): void
    {
        $column = TextColumn::make('name')->createFilter((object) ['name_count' => 0]);

        $this->assertNull(FilamentColumnFilters::getColumnFilter($column));
    }
}
