<?php

namespace JundFauz\FilamentKompact\Table;

use Filament\Tables\Columns\Column;
use JundFauz\FilamentKompact\Helper\Filter;
use Zvizvi\FilamentColumnFilters\Filters\ColumnFilter;

class ColumnMacros extends Column
{
    public static function createFilter(): void
    {
        if (class_exists(ColumnFilter::class)) {
            Column::macro(__FUNCTION__, fn (?object $stats = null) => Filter::createForColumn($this, $stats));
        }
    }
}