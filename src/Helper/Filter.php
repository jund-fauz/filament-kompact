<?php

namespace JundFauz\FilamentKompact\Helper;

use BackedEnum;
use Closure;
use Filament\Tables\Columns\Column;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use JundFauz\EloquentKompact\Builder\Str;
use JundFauz\EloquentKompact\Column\Checker;
use JundFauz\EloquentKompact\Column\Convert;
use JundFauz\EloquentKompact\Query\RawQuery;
use JundFauz\FilamentKompact\UI\Formatter\Format;
use UnitEnum;
use Zvizvi\FilamentColumnFilters\Filters\ColumnFilter;
use function JundFauz\EloquentKompact\array_flat;
use function JundFauz\EloquentKompact\collect;
use function JundFauz\EloquentKompact\is_not_null;
use function JundFauz\EloquentKompact\not_in_array;

final class Filter
{
    /** @var array<int, Closure(ColumnFilter, Column): void> */
    protected static array $filterConfigurations = [];

    /** @var (Closure(Builder|Relation|null &$query, Collection<int, string> &$relationNames, string $columnName, Closure(Builder, iterable<string>): Builder $walk): void)|null */
    protected static ?Closure $optionQueryResolver = null;

    /**
     * @param class-string<Model> $modelClass
     * @param string|array ...$columns
     * @return Model
     * @internal
     */
    public static function getCount(string $modelClass, string|array ...$columns): Model
    {
        array_flat($columns);
        $selects = collect([]);
        $relationalColumns = [];

        foreach ($columns as $column) {
            if (Str::of($column)->contains('.')) {
                $relationalColumns[] = $column;
            } else {
                $selects->push(RawQuery::create($column)
                    ->replaceIfNull(RawQuery::string())
                    ->count()
                    ->as("{$column}_count")
                    ->build());
            }
        }

        $result = $selects->isEmpty()
            ? new $modelClass()
            : $modelClass::selectRaw($selects->implode(','))->first();

        if ($result) {
            foreach ($relationalColumns as $relationalColumn) {
                $result->{"{$relationalColumn}_count"} = 1;
            }
        }

        return $result;
    }

    /**
     * Register a global configuration that runs on every column filter.
     *
     * @param Closure(ColumnFilter, Column): void $callback
     */
    public static function configureUsing(Closure $callback): void
    {
        self::$filterConfigurations[] = $callback;
    }

    /**
     * Override how the options query of a relational column is built.
     * Take `$query` and `$relationNames` by reference (`&`) and reassign them to replace the defaults.
     *
     * @param (Closure(Builder|Relation|null &$query, Collection<int, string> &$relationNames, string $columnName, Closure(Builder, iterable<string>): Builder $walk): void)|null $callback
     */
    public static function resolveOptionQueryUsing(?Closure $callback): void
    {
        self::$optionQueryResolver = $callback;
    }

    /**
     * Attach a select column filter to the column, skipping it when the stats count is empty.
     *
     * @param Column $column
     * @param object|null $stats Result of {@see Filter::getCount()}, holding `{column}_count` attributes.
     * @param bool|null $searchable
     * @param (Closure(ColumnFilter, Column): void)|null $customConfiguration
     * @param (Closure(Builder|Relation|null &$query, Collection<int, string> &$relationNames, string $columnName, Closure(Builder, iterable<string>): Builder $walk): void)|null $customFilterOptionConfiguration Same contract as {@see Filter::resolveOptionQueryUsing()}.
     * @return Column
     */
    public static function createForColumn(Column $column, ?object $stats = null, ?bool $searchable = false, ?Closure $customConfiguration = null, ?Closure $customFilterOptionConfiguration = null): Column
    {
        $columnName = $column->getName();

        if ($stats) {
            $key = "{$columnName}_count";

            if (! $stats->{$key}) {
                return $column;
            }
        }

        $filter = ColumnFilter::select()
            ->searchable()
            ->options(function () use ($customFilterOptionConfiguration, $columnName, $column) {
                if (Checker::isBoolean($columnName)) {
                    return Convert::booleanToString($columnName);
                }

                $path = Str::of($columnName);
                $model = $column->getTable()->getModel();

                if ($path->contains('.')) {
                    $relationNames = $path->explode('.');
                    $relationColumn = $relationNames->pop();
                    $query = $column->getTable()->getQuery();

                    $walk = function (Builder $query, iterable $relationNames): Builder {
                        foreach ($relationNames as $relationModel) {
                            $relation = $query->getModel()->{$relationModel}();
                            /** @var Model $relationModelClass */
                            $relationModelClass = get_class($relation->getRelated());

                            switch (true) {
                                case $relation instanceof BelongsTo:
                                    $foreignKey = $relation->getQualifiedForeignKeyName();
                                    $ownerKey = $relation->getOwnerKeyName();
                                    $subquery = $query->select($foreignKey)->whereNotNull($foreignKey)->distinct();
                                    $query = $relationModelClass::query()->whereIn($ownerKey, $subquery);
                                    break;
                                case $relation instanceof HasOneOrMany:
                                    $parentKey = $relation->getQualifiedParentKeyName();
                                    $foreignKey = $relation->getForeignKeyName();
                                    $subquery = $query->select($parentKey)->whereNotNull($parentKey)->distinct();
                                    $query = $relationModelClass::query()->whereIn($foreignKey, $subquery);
                                    break;
                                default:
                                    $query = $relationModelClass::query();
                            }
                        }
                        return $query;
                    };

                    if (is_not_null(self::$optionQueryResolver)) {
                        $resolver = self::$optionQueryResolver;
                        $resolver($query, $relationNames, $columnName, $walk);
                    }

                    if ($customFilterOptionConfiguration) {
                        $customFilterOptionConfiguration($query, $relationNames, $columnName, $walk);
                    }

                    $filterData = $walk($query, $relationNames)
                        ->distinct()
                        ->whereNotNull($relationColumn)
                        ->where($relationColumn, '!=', '')
                        ->pluck($relationColumn);
                } else {
                    $tableName = (new $model)->getTable();
                    $nullable = Cache::remember("schema_nullable_$tableName", Convert::dayToSecond(15), function () use ($tableName) {
                        return collect(Schema::getColumns($tableName))
                            ->where('nullable', true)
                            ->pluck('name')
                            ->toArray();
                    });

                    $required = not_in_array($columnName, $nullable);

                    $filterData = $column
                        ->getTable()
                        ->getQuery()
                        ->distinct()
                        ->whereNotNull($columnName)
                        ->whereNot($columnName, '')
                        ->pluck($columnName);
                }

                // Columns with the _enum suffix are cast from the model to enum objects,
                // while filter options must be scalars so they can be cast to strings.
                $toScalar = fn ($item) => match (true) {
                    $item instanceof BackedEnum => $item->value,
                    $item instanceof UnitEnum => $item->name,
                    is_float($item) => (string) $item,
                    default => $item,
                };

                $format = fn ($item) => match (true) {
                    Checker::isGram($columnName) => Format::gram($item),
                    Checker::isPcs($columnName) => Format::pcs($item),
                    Checker::isPercentage($columnName) => Format::percentage($item),
                    Checker::isEnum($columnName) => $toScalar($item),
                    default => $item
                };

                // Plucked as a list, because a float key would be truncated to an int.
                $filterData = $filterData->mapWithKeys(
                    fn ($value) => [$toScalar($value) => $format($value)]
                );

                if (isset($required) && $required) {
                    $filterData = $filterData->reject(fn ($value) => blank($value));
                }

                return $filterData->all();
            });

        foreach (self::$filterConfigurations as $filterConfiguration) {
            $filterConfiguration($filter, $column);
        }

        if ($customConfiguration) {
            $customConfiguration($filter, $column);
        }

        $column->columnFilter($filter);

        if ($searchable) {
            $column->searchable();
        }

        return $column;
    }
}