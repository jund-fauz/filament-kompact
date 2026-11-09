<?php

namespace JundFauz\FilamentKompact\Table;

use Filament\Tables\Columns\TextColumn;
use Zvizvi\FilamentColumnFilters\Filters\ColumnFilter;

/**
 * Created by: damar-glh
 */
class ColumnTimestamps
{
    public static function tableColumns(bool $withDeletedAt = true): array
    {
        $columns = [
            TextColumn::make('created_at')
                ->dateTime('d-m-Y, H:i')
                ->sortable()
                ->toggleable()
                ->columnFilter(ColumnFilter::date()),

            TextColumn::make('updated_at')
                ->dateTime('d-m-Y, H:i')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ];

        if ($withDeletedAt) {
            $columns[] = TextColumn::make('deleted_at')
                ->dateTime('d-m-Y, H:i')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true);
        }

        return $columns;
    }
}
