<?php

namespace JundFauz\FilamentKompact\UI\Formatter;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;

class Format
{
    public static function gram(mixed $num): string
    {
        if (blank($num)) {
            return '-';
        }

        return BigDecimal::of((string) $num)->toScale(3, RoundingMode::Ceiling) . ' gr';
    }

    public static function pcs(mixed $num): string
    {
        if (blank($num)) {
            return '-';
        }

        return number_format((int)$num) . ' pcs';
    }

    public static function percentage(mixed $num): string
    {
        if (blank($num)) {
            return '-';
        }

        return "$num%";
    }

    public static function carat(mixed $num): string
    {
        if (blank($num)) {
            return '-';
        }

        return "$num K";
    }

    public static function time(mixed $value): string
    {
        if (blank($value)) {
            return '-';
        }

        return substr($value, 0, 5);
    }
}
