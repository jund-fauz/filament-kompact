<?php

namespace JundFauz\FilamentKompact\Tests\Fixtures\Filament\Pages;

use Filament\Resources\Pages\CreateRecord;
use JundFauz\FilamentKompact\Tests\Fixtures\Filament\ProductResource;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
