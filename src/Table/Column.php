<?php

namespace JundFauz\FilamentKompact\Table;

use BackedEnum;
use Closure;
use Exception;
use Filament\Tables\Columns\Column as FilamentColumn;
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
use JundFauz\FilamentKompact\UI\Formatter\Format;
use UnitEnum;
use Zvizvi\FilamentColumnFilters\Filters\ColumnFilter;
use function JundFauz\EloquentKompact\collect;

class Column extends FilamentColumn
{
    /**
     * Create filter per column on a filament table using `zvizvi/filament-column-filters` plugin
     * Configure column to filtered by ... (TODO: Finish the docs)
     *
     * @param Closure(ColumnFilter): void|null $customConfiguration
     * @param Closure(Builder|Relation|null, Collection<int, string>, Closure(Builder, iterable): Builder): void|null $customFilterOptionConfiguration
     * @return Closure(object|null, bool): FilamentColumn
     * @throws Exception
     */
    public static function createFilter(?Closure $customConfiguration = null, ?Closure $customFilterOptionConfiguration = null): Closure
    {
        return class_exists(ColumnFilter::class)
            ? function (?object $stats = null, bool $searchable = false) use ($customConfiguration, $customFilterOptionConfiguration) {
                $columnName = $this->getName();
                $column = $this;

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

                            if ($customFilterOptionConfiguration) {
                                $customFilterOptionConfiguration($query, $relationNames, $walk);
                            }

                            $filterData = $walk($query, $relationNames)
                                ->distinct()
                                ->whereNotNull($relationColumn)
                                ->where($relationColumn, '!=', '')
                                ->pluck($relationColumn, $relationColumn);
                        } else {
                            $tableName = (new $model)->getTable();
                            $nullable = Cache::remember("schema_nullable_$tableName", Convert::dayToSecond(15), function () use ($tableName) {
                                return collect(Schema::getColumns($tableName))
                                    ->where('nullable', true)
                                    ->pluck('name')
                                    ->toArray();
                            });

                            $required = ! in_array($columnName, $nullable);

                            $filterData = $column
                                ->getTable()
                                ->getQuery()
                                ->distinct()
                                ->whereNotNull($columnName)
                                ->whereNot($columnName, '')
                                ->pluck($columnName, $columnName);
                        }

                        // Columns with the _enum suffix are cast from the model to enum objects,
                        // while filter options must be scalars so they can be cast to strings.
                        $toScalar = fn ($item) => match (true) {
                            $item instanceof BackedEnum => $item->value,
                            $item instanceof UnitEnum => $item->name,
                            default => $item,
                        };

                        $format = fn ($item) => match (true) {
                            Checker::isGram($columnName) => Format::gram($item),
                            Checker::isPcs($columnName) => Format::pcs($item),
                            Checker::isPercentage($columnName) => Format::percentage($item),
                            Checker::isEnum($columnName) => $toScalar($item),
                            default => $item
                        };

                        $filterData = $filterData->mapWithKeys(
                            fn ($label, $value) => [$toScalar($value) => $format($label)]
                        );

                        if (isset($required) && $required) {
                            $filterData = $filterData->reject(fn ($value) => blank($value));
                        }

                        return $filterData->all();
                    });

                if ($customConfiguration) {
                    $customConfiguration($filter);
                }

                $column->columnFilter($filter);

                if ($searchable) {
                    $column->searchable();
                }

                return $column;
            }
        : throw new Exception('Install zvizvi/filament-column-filters to use createFilter()');
    }
}