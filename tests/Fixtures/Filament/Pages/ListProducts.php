<?php

namespace JundFauz\FilamentKompact\Tests\Fixtures\Filament\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use JundFauz\FilamentKompact\Tests\Fixtures\Filament\ProductResource;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
