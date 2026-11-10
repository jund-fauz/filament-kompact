<?php

namespace JundFauz\FilamentKompact\Tests\Livewire;

use Filament\Forms\Components\Repeater;
use JundFauz\FilamentKompact\Tests\Fixtures\Filament\Pages\CreateProduct;
use JundFauz\FilamentKompact\Tests\Fixtures\Filament\Pages\EditProduct;
use JundFauz\FilamentKompact\Tests\Fixtures\Product;
use JundFauz\FilamentKompact\Tests\PanelTestCase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;

/**
 * Repeater::minOneItem() inside the create and edit pages of a Filament resource
 */
class ProductFormTest extends PanelTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Repeater item keys are random UUIDs; make them predictable
        Repeater::fake();
    }

    protected function tearDown(): void
    {
        Repeater::fake(false);

        parent::tearDown();
    }

    #[Test]
    public function min_one_item_starts_the_create_form_with_one_item(): void
    {
        $state = Livewire::test(CreateProduct::class)->instance()->form->getRawState();

        $this->assertCount(1, $state['variants_array']);
    }

    #[Test]
    public function min_one_item_saves_the_items(): void
    {
        Livewire::test(CreateProduct::class)
            ->fillForm([
                'name' => 'Milk',
                'variants_array' => [['label' => '250 ml'], ['label' => '1 l']],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame([['label' => '250 ml'], ['label' => '1 l']], Product::where('name', 'Milk')->first()->variants_array);
    }

    #[Test]
    public function min_one_item_requires_at_least_one_item(): void
    {
        Livewire::test(CreateProduct::class)
            ->fillForm(['name' => 'Milk', 'variants_array' => []])
            ->call('create')
            ->assertHasFormErrors(['variants_array' => 'min']);

        $this->assertNull(Product::where('name', 'Milk')->first());
    }

    #[Test]
    public function the_delete_action_of_min_one_item_removes_an_item(): void
    {
        $component = Livewire::test(CreateProduct::class)
            ->fillForm(['variants_array' => ['a' => ['label' => 'S'], 'b' => ['label' => 'M']]])
            ->callFormComponentAction('variants_array', 'delete', arguments: ['item' => 'a']);

        $this->assertSame(['b' => ['label' => 'M']], $component->instance()->form->getRawState()['variants_array']);
    }

    #[Test]
    public function min_one_item_hides_the_delete_button_of_the_last_item_on_the_edit_page(): void
    {
        $product = $this->products['apple'];
        $product->update(['variants_array' => [['label' => 'Red']]]);

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->assertOk()
            ->assertSeeHtml('visibility: hidden;');

        $product->update(['variants_array' => [['label' => 'Red'], ['label' => 'Green']]]);

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->assertOk()
            ->assertDontSeeHtml('visibility: hidden;');
    }

    #[Test]
    public function the_edit_page_saves_the_items(): void
    {
        $product = $this->products['apple'];

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->fillForm(['variants_array' => [['label' => 'Green']]])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame([['label' => 'Green']], $product->refresh()->variants_array);
    }
}
