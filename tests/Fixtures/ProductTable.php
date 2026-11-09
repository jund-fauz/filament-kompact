<?php

namespace JundFauz\FilamentKompact\Tests\Fixtures;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use JundFauz\FilamentKompact\Concerns\KompactTable;

class ProductTable extends KompactTable
{
    public static array $columnStats = [];
    public static bool $iconOnly = true;
    public static bool $timestamps = true;
    public static bool $deletedAt = true;
    public static bool $viewAction = true;

    public static function configure(Table $table): Table
    {
        static::$statsColumns = static::$columnStats;
        static::$recordActionOnlyShowIcon = static::$iconOnly;
        static::$showTimestamps = static::$timestamps;
        static::$showDeletedAt = static::$deletedAt;

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
}
