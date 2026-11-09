<?php

namespace JundFauz\FilamentKompact\Table;

use Closure;
use Filament\Tables\Columns\TextColumn;
use JundFauz\FilamentKompact\Concerns\Trait\Formattable;

/**
 * @internal
 */
final class TextColumnMacros extends TextColumn
{
    use Formattable;

    public static function formatId(): void
    {
        TextColumn::macro(__FUNCTION__, self::idFormatter());
    }

    public static function searchSortable(): void
    {
        TextColumn::macro(__FUNCTION__, function (array|bool|Closure $sortableCondition = true, array|bool|Closure $searchableCondition = true) {
            return $this
                ->searchable($searchableCondition)
                ->sortable($sortableCondition);
        });
    }
}