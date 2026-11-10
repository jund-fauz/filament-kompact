<?php

namespace JundFauz\FilamentKompact\Tests\Fixtures\Filament;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use JundFauz\FilamentKompact\Concerns\KompactTable;

/**
 * The table of {@see ProductResource}: a KompactTable with the column macros
 */
class ProductsTable extends KompactTable
{
    protected static array $statsColumns = ['name', 'brand', 'category.name', 'active_is', 'weight_gram'];

    public static function configure(Table $table): Table
    {
        return static::table($table);
    }

    protected static function getColumns(): array
    {
        return [
            TextColumn::make('id')->label('Code')->formatId('PRD-'),
            TextColumn::make('name')->searchSortable(),
            TextColumn::make('brand'),
            TextColumn::make('category.name')->label('Category'),
            TextColumn::make('active_is')->label('Active'),
            TextColumn::make('weight_gram')->label('Weight'),
        ];
    }
}
