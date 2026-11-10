<?php

namespace JundFauz\FilamentKompact\Tests\Fixtures;

use Closure;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use JundFauz\FilamentKompact\Concerns\KompactTable;
use Zvizvi\FilamentColumnFilters\Filters\ColumnFilter;

/**
 * A KompactTable whose options and hooks can be changed per test
 */
class ProductTable extends KompactTable
{
    public static array $columnStats = [];
    public static bool $iconOnly = true;
    public static bool $timestamps = true;
    public static bool $deletedAt = true;
    public static bool $viewAction = true;
    public static bool $persist = true;

    /** @var (Closure(ColumnFilter, Column): void)|null */
    public static ?Closure $columnFilterHook = null;

    public static ?Closure $queryHook = null;

    public static function reset(): void
    {
        static::$columnStats = [];
        static::$iconOnly = true;
        static::$timestamps = true;
        static::$deletedAt = true;
        static::$viewAction = true;
        static::$persist = true;
        static::$columnFilterHook = null;
        static::$queryHook = null;
    }

    public static function configure(Table $table): Table
    {
        static::$statsColumns = static::$columnStats;
        static::$recordActionOnlyShowIcon = static::$iconOnly;
        static::$showTimestamps = static::$timestamps;
        static::$showDeletedAt = static::$deletedAt;
        static::$persistInSession = static::$persist;

        return static::table($table);
    }

    protected static function getColumns(): array
    {
        return [
            TextColumn::make('name'),
            TextColumn::make('brand'),
        ];
    }

    protected static function getToggledViewAction(): bool
    {
        return static::$viewAction;
    }

    protected static function configureColumnFilter(ColumnFilter $filter, Column $column): void
    {
        if (static::$columnFilterHook) {
            (static::$columnFilterHook)($filter, $column);
        }
    }

    protected static function getModifiedQuery(): Closure
    {
        return static::$queryHook ?? parent::getModifiedQuery();
    }
}
