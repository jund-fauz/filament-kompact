<?php

namespace JundFauz\FilamentKompact\Infolist;

use Filament\Infolists\Components\TextEntry;
use JundFauz\FilamentKompact\Concerns\Trait\Formattable;

class TextEntryMacros extends TextEntry
{
    use Formattable;

    public static function formatId(): void
    {
        TextEntry::macro(__FUNCTION__, self::idFormatter(...));
    }
}