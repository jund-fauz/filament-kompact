<?php

namespace JundFauz\FilamentKompact\Form;

use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;

/**
 * @internal
 */
final class RepeaterMacros extends Repeater
{
    public static function minOneItem(): void
    {
        Repeater::macro('minOneItem', function () {
            return $this
                ->defaultItems(1)
                ->minItems(1)
                ->deleteAction(fn (Action $action) => $action->extraAttributes(
                    fn (Repeater $repeater) => count($repeater->getState() ?? []) <= 1
                        ? [
                            'style' => 'visibility: hidden;',
                            'tabindex' => '-1',
                            'aria-hidden' => 'true'
                        ]
                        : []
                ));
        });
    }
}