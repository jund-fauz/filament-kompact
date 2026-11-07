<?php

namespace JundFauz\FilamentKompact\Concerns\Trait;

use Closure;
use JundFauz\EloquentKompact\Builder\Str;

trait Formattable
{
    public static function idFormatter(): Closure
    {
        return fn (string $prefix, ?int $digit = 3) => $this->formatStateUsing(fn (string $state) => Str::of($state)->padLeft($digit, '0')->addAtStart($prefix)->value());
    }
}