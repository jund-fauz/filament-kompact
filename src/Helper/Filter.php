<?php

namespace JundFauz\FilamentKompact\Helper;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use JundFauz\EloquentKompact\Query\RawQuery;
use function JundFauz\EloquentKompact\array_flat;
use function JundFauz\EloquentKompact\collect;

/**
 * @internal
 */
final class Filter
{
    /**
     * @param class-string<Model> $modelClass
     * @param string|array ...$columns
     */
    public static function getCount(string $modelClass, string ...$columns): Model
    {
        array_flat($columns);
        $selects = collect();
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
            : $modelClass::selectRaw($selects->implode(','));

        if ($result) {
            foreach ($relationalColumns as $relationalColumn) {
                $result->{"{$relationalColumn}_count"} = 1;
            }
        }

        return $result;
    }
}