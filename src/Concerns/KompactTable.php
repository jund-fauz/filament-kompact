<?php

namespace JundFauz\FilamentKompact\Concerns;

use Dam\FilamentKit\Components\Tables\ColumnTimestamps;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Schemas\Components\Component;
use Filament\Support\Enums\IconSize;
use Filament\Tables\Columns\Column;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use JundFauz\FilamentKompact\Helper\Filter;
use function JundFauz\EloquentKompact\collect;
use function JundFauz\EloquentKompact\merge_as_array;
use function JundFauz\EloquentKompact\not;

abstract class KompactTable
{
    protected static array $statsColumns = [];
    protected static bool $recordActionOnlyShowIcon = true;
    protected static bool $showTimestamps = true;
    protected static bool $showDeletedAt = true;

    /** Configuration */

    /**
     * @return array<int, Column>
     */
    public static function getColumns(): array
    {
        return [];
    }

    /**
     * @return array<int, string>
     */
    public static function getStatsColumn(): array
    {
        return static::$statsColumns;
    }

    /**
     * @return array<int, Component>
     */
    public static function getFilter(): array
    {
        return [
            TrashedFilter::make()
        ];
    }

    public static function getToggledViewAction(): bool
    {
        return true;
    }

    /**
     * Credit: damar-glh (https://github.com/damar-glh)
     *
     * @param Action $action
     * @return Action
     */
    protected static function withIconOnlyStyle(Action $action): Action
    {
        return $action
            ->hiddenLabel()
            ->iconSize(IconSize::Medium)
            ->tooltip($action->getLabel());
    }

    public static function getRecordActions(): array
    {
        $actions = collect(
            EditAction::make()
                ->visible(static::notTrashed(...)),
            DeleteAction::make()
                ->visible(static::notTrashed(...)),
            ForceDeleteAction::make()
                ->visible(static::trashed(...)),
            RestoreAction::make()
                ->visible(static::trashed(...))
        );

        if (static::$recordActionOnlyShowIcon) {
            $actions = $actions->map(static::withIconOnlyStyle(...));
        }

        if (static::getToggledViewAction()) {
            $actions->unshift(static::withIconOnlyStyle(ViewAction::make())->extraAttributes(['style' => 'display:none']));
        }

        return $actions->toArray();
    }

    /**
     * @return array<int, ActionGroup>
     */
    public static function getToolbarActions(): array
    {
        return [
            BulkActionGroup::make([
                DeleteBulkAction::make(),
                RestoreBulkAction::make(),
                ForceDeleteBulkAction::make(),
            ])
        ];
    }

    /** Table Creation */
    public static function table(Table $table): Table
    {
        $stats = null;
        if ($statsColumns = static::getStatsColumn()) {
            $stats = Filter::getCount($table->getModel()::class, $statsColumns);
        }
        $columns = collect(static::getColumns());
        if (static::$showTimestamps) {
            $columns->push(...ColumnTimestamps::tableColumns());
        }
        if ($statsColumns) {
            foreach ($columns as $column) {
                /** @var Column $column */
                $column->createFilter
            }
        }
    }

    protected static function trashed(Model $record): bool
    {
        return method_exists($record, 'trashed') && $record->trashed();
    }

    protected static function notTrashed(Model $record): bool
    {
        return not(static::trashed($record));
    }
}