<?php

namespace JundFauz\FilamentKompact\Table;

use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\TextColumn;
use Zvizvi\FilamentColumnFilters\Filters\ColumnFilter;

/**
 * Created by: damar-glh
 */
class ColumnTimestamps
{
    public static function tableColumns(bool $withDeletedAt = true): array
    {
        $createdAtColumn = TextColumn::make('created_at')
            ->dateTime('d-m-Y, H:i')
            ->sortable()
            ->toggleable();
        if (Column::hasMacro('columnFilter')) {
            $createdAtColumn->columnFilter(ColumnFilter::date());
        }
        $columns = [
            $createdAtColumn,
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
