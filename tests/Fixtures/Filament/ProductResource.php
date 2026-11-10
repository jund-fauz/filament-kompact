<?php

namespace JundFauz\FilamentKompact\Tests\Fixtures\Filament;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use JundFauz\FilamentKompact\Tests\Fixtures\Filament\Pages\CreateProduct;
use JundFauz\FilamentKompact\Tests\Fixtures\Filament\Pages\EditProduct;
use JundFauz\FilamentKompact\Tests\Fixtures\Filament\Pages\ListProducts;
use JundFauz\FilamentKompact\Tests\Fixtures\Filament\Pages\ViewProduct;
use JundFauz\FilamentKompact\Tests\Fixtures\Product;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required(),
            TextInput::make('brand'),
            Repeater::make('variants_array')
                ->label('Variants')
                ->schema([TextInput::make('label')->required()])
                ->minOneItem(),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('id')->label('Code')->formatId('PRD-'),
            TextEntry::make('name'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return ProductsTable::configure($table);
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProducts::route('/'),
            'create' => CreateProduct::route('/create'),
            'view' => ViewProduct::route('/{record}'),
            'edit' => EditProduct::route('/{record}/edit'),
        ];
    }
}
