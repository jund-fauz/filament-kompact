<?php

namespace JundFauz\FilamentKompact;

use JundFauz\FilamentKompact\Form\RepeaterMacros;
use JundFauz\FilamentKompact\Infolist\TextEntryMacros;
use JundFauz\FilamentKompact\Table\ColumnMacros;
use JundFauz\FilamentKompact\Table\TextColumnMacros;

class Macros
{
    public const array lists = [
        RepeaterMacros::class,
        TextEntryMacros::class,
        TextColumnMacros::class,
        ColumnMacros::class
    ];
}