<?php

namespace JundFauz\FilamentKompact\Tests\Livewire;

use JundFauz\FilamentKompact\Tests\Fixtures\Filament\Pages\ViewProduct;
use JundFauz\FilamentKompact\Tests\PanelTestCase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;

/**
 * TextEntry::formatId() inside the view page of a Filament resource
 */
class ViewProductTest extends PanelTestCase
{
    #[Test]
    public function format_id_formats_the_id_entry(): void
    {
        Livewire::test(ViewProduct::class, ['record' => $this->products['bread']->getRouteKey()])
            ->assertOk()
            ->assertSee('PRD-002')
            ->assertSee('Bread');
    }

    #[Test]
    public function format_id_formats_a_trashed_record(): void
    {
        Livewire::test(ViewProduct::class, ['record' => $this->products['water']->getRouteKey()])
            ->assertOk()
            ->assertSee('PRD-004');
    }
}
