<?php

namespace JundFauz\FilamentKompact\Concerns;

use Closure;
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
use Filament\Support\Enums\IconSize;
use Filament\Tables\Columns\Column;
use Filament\Tables\Filters\BaseFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use JundFauz\FilamentKompact\Helper\Filter;
use JundFauz\FilamentKompact\Table\ColumnTimestamps;
use Zvizvi\FilamentColumnFilters\Filters\ColumnFilter;
use function JundFauz\EloquentKompact\collect;
use function JundFauz\EloquentKompact\not;

abstract class KompactTable
{
    protected static array $statsColumns = [];
    protected static bool $recordActionOnlyShowIcon = true;
    protected static bool $showTimestamps = true;
    protected static bool $showDeletedAt = true;
    protected static bool $persistInSession = true;

    /** Configuration */

    /**
     * @return array<int, Column>
     */
    protected static function getColumns(): array
    {
        return [];
    }

    /**
     * @return array<int, string>
     */
    protected static function getStatsColumn(): array
    {
        return static::$statsColumns;
    }

    /**
     * @return array<int, BaseFilter>
     */
    protected static function getFilters(): array
    {
        return [
            TrashedFilter::make()
        ];
    }

    protected static function getToggledViewAction(): bool
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

    protected static function getRecordActions(): array
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
    protected static function getToolbarActions(): array
    {
        return [
            BulkActionGroup::make([
                DeleteBulkAction::make(),
                RestoreBulkAction::make(),
                ForceDeleteBulkAction::make(),
            ])
        ];
    }

    /**
     * Per-table hook to adjust each column filter, e.g. by checking `$column->getName()`.
     */
    protected static function configureColumnFilter(ColumnFilter $filter, Column $column): void
    {

    }

    protected static function getModifiedQuery(): Closure
    {
        return fn (Builder $query) => $query;
    }

    /** Table Creation, by @jund-fauz & @damar-glh */
    protected static function table(Table $table): Table
    {
        $stats = null;
        if ($statsColumns = static::getStatsColumn()) {
            $stats = Filter::getCount($table->getModel(), $statsColumns);
        }
        $columns = collect(static::getColumns());
        if (static::$showTimestamps) {
            $columns->push(...ColumnTimestamps::tableColumns(static::$showDeletedAt));
        }
        if ($statsColumns && class_exists(ColumnFilter::class)) {
            foreach ($columns as $column) {
                /** @var Column $column */
                Filter::createForColumn($column, $stats, customConfiguration: static::configureColumnFilter(...));
            }
        }

        return $table
            ->columns($columns->toArray())
            ->persistInSession(static::$persistInSession)
            ->filters(static::getFilters())
            ->recordActions(static::getRecordActions())
            ->toolbarActions(static::getToolbarActions())
            ->modifyQueryUsing(static::getModifiedQuery());
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