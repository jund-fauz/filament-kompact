<?php

namespace JundFauz\FilamentKompact\Tests\Browser;

use JundFauz\FilamentKompact\Tests\Fixtures\Product;
use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\Test;

/**
 * Repeater::minOneItem() and TextEntry::formatId() in a real browser
 */
class ProductFormTest extends BrowserTestCase
{
    private const DELETE_ITEM = '.fi-fo-repeater-item [aria-label="Delete"]';

    #[Test]
    public function min_one_item_starts_with_one_item_without_a_delete_button(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/admin/products/create')
                ->waitForText('Variants')
                ->assertPresent('.fi-fo-repeater-item')
                ->assertMissing(self::DELETE_ITEM);

            $this->assertCount(1, $browser->elements('.fi-fo-repeater-item'));
        });
    }

    #[Test]
    public function min_one_item_shows_the_delete_buttons_once_there_are_two_items_and_hides_it_again(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/admin/products/create')
                ->waitForText('Add to variants')
                ->press('Add to variants')
                ->waitUntil('document.querySelectorAll(".fi-fo-repeater-item").length === 2')
                ->waitFor(self::DELETE_ITEM);

            foreach ($browser->elements(self::DELETE_ITEM) as $button) {
                $this->assertTrue($button->isDisplayed());
            }

            $browser->click(self::DELETE_ITEM)
                ->waitUntil('document.querySelectorAll(".fi-fo-repeater-item").length === 1')
                ->assertMissing(self::DELETE_ITEM);
        });
    }

    #[Test]
    public function min_one_item_saves_the_items_of_a_new_record(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/admin/products/create')
                ->waitForText('Variants')
                ->type('[wire\:model="data.name"]', 'Milk')
                ->type('.fi-fo-repeater-item input', '1 l')
                ->press('Create')
                ->waitForText('PRD-005');
        });

        $this->assertSame([['label' => '1 l']], array_values(Product::where('name', 'Milk')->first()->variants_array));
    }

    #[Test]
    public function the_view_page_shows_the_formatted_id_of_a_trashed_record(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/admin/products/' . $this->products['water']->getKey())
                ->waitForText('PRD-004')
                ->assertSee('Water');
        });
    }
}
