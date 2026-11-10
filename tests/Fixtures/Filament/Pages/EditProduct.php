<?php

namespace JundFauz\FilamentKompact\Tests\Fixtures\Filament\Pages;

use Filament\Resources\Pages\EditRecord;
use JundFauz\FilamentKompact\Tests\Fixtures\Filament\ProductResource;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
