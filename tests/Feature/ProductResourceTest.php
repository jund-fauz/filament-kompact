<?php

namespace JundFauz\FilamentKompact\Tests\Feature;

use JundFauz\FilamentKompact\Tests\PanelTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * The pages of a Filament resource that uses the package, requested over HTTP
 */
class ProductResourceTest extends PanelTestCase
{
    #[Test]
    public function a_guest_is_redirected_to_the_login_page(): void
    {
        auth()->logout();

        $this->get('/admin/products')->assertRedirect('/admin/login');
    }

    #[Test]
    public function the_list_page_shows_the_records_with_their_formatted_id(): void
    {
        $this->get('/admin/products')
            ->assertOk()
            ->assertSeeInOrder(['PRD-001', 'Apple', 'PRD-002', 'Bread', 'PRD-003', 'Cola'])
            ->assertDontSee('PRD-004')
            ->assertDontSee('Water');
    }

    #[Test]
    public function the_list_page_has_the_timestamp_columns(): void
    {
        $this->get('/admin/products')
            ->assertOk()
            ->assertSee('Created at')
            ->assertSee('Updated at')
            ->assertSee('Deleted at');
    }

    #[Test]
    public function the_list_page_renders_the_column_filter_options_with_their_format(): void
    {
        $this->get('/admin/products')
            ->assertOk()
            ->assertSee('filament-column-filters')
            ->assertSee('150.500 gr')
            ->assertSee('500.000 gr')
            ->assertSee('Not active')
            ->assertSee('Food')
            ->assertSee('Drink')
            ->assertSee('Acme')
            ->assertDontSee('Unused');
    }

    #[Test]
    public function the_list_page_hides_the_view_action(): void
    {
        $this->get('/admin/products')
            ->assertOk()
            ->assertSee('display:none', escape: false);
    }

    #[Test]
    public function the_create_page_starts_the_repeater_with_one_item_without_a_delete_button(): void
    {
        $this->get('/admin/products/create')
            ->assertOk()
            ->assertSee('Variants')
            ->assertSee('visibility: hidden;', escape: false);
    }

    #[Test]
    public function the_view_page_shows_the_formatted_id(): void
    {
        $this->get('/admin/products/' . $this->products['apple']->getRouteKey())
            ->assertOk()
            ->assertSee('PRD-001')
            ->assertSee('Apple');
    }

    #[Test]
    public function the_edit_page_shows_the_items_of_the_repeater(): void
    {
        $this->products['apple']->update(['variants_array' => [['label' => 'Red'], ['label' => 'Green']]]);

        $this->get('/admin/products/' . $this->products['apple']->getRouteKey() . '/edit')
            ->assertOk()
            ->assertSee('Red')
            ->assertSee('Green')
            ->assertDontSee('visibility: hidden;', escape: false);
    }
}
