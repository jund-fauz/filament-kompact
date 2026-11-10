<?php

namespace JundFauz\FilamentKompact\Tests\Fixtures\Filament\Pages;

use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use JundFauz\FilamentKompact\Tests\Fixtures\Filament\ProductResource;

class ViewProduct extends ViewRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [EditAction::make()];
    }
}
