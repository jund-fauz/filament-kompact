<?php

namespace JundFauz\FilamentKompact\Tests\Browser;

use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverKeys;
use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\Test;

/**
 * KompactTable, the column macros and the column filters in a real browser
 */
class ProductTableTest extends BrowserTestCase
{
    #[Test]
    public function it_shows_the_records_with_their_formatted_id(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/admin/products')
                ->waitForText('PRD-001')
                ->assertSee('PRD-002')
                ->assertSee('PRD-003')
                ->assertDontSee('PRD-004')
                ->assertSee('Apple')
                ->assertDontSee('Water');
        });
    }

    #[Test]
    public function it_only_shows_the_created_at_timestamp_column(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/admin/products')
                ->waitForText('PRD-001')
                ->assertSee('Created at')
                ->assertDontSee('Updated at')
                ->assertDontSee('Deleted at');
        });
    }

    #[Test]
    public function the_record_actions_are_icons_and_the_view_action_is_hidden(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/admin/products')
                ->waitForText('PRD-001')
                ->assertVisible('.fi-ta-actions [aria-label="Edit"]')
                ->assertVisible('.fi-ta-actions [aria-label="Delete"]')
                ->assertMissing('.fi-ta-actions [aria-label="View"]')
                ->assertMissing('.fi-ta-actions [aria-label="Restore"]')
                ->assertSeeNothingIn('.fi-ta-actions');
        });
    }

    #[Test]
    public function clicking_a_row_opens_the_view_page_with_the_formatted_id(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/admin/products')
                ->waitForText('Bread')
                ->clickLink('Bread')
                ->waitForLocation('/admin/products/' . $this->products['bread']->getKey())
                ->waitForText('PRD-002')
                ->assertSee('Bread');
        });
    }

    #[Test]
    public function the_delete_action_moves_the_record_to_the_trash(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/admin/products')
                ->waitForText('PRD-003');

            $this->rowAction($browser, 'PRD-003', 'Delete');

            $browser->waitFor('.fi-modal-window')
                ->within('.fi-modal-window', fn (Browser $modal) => $modal->press('Delete'))
                ->waitUntilMissingText('PRD-003')
                ->assertSee('PRD-001');
        });

        $this->assertSoftDeleted($this->products['cola']);
    }

    #[Test]
    public function the_column_filter_of_a_column_filters_the_records(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/admin/products')
                ->waitForText('PRD-003');

            $this->openColumnFilter($browser, 'Brand');

            $browser->within('.fcf-panel--open', function (Browser $panel) {
                $panel->assertSee('Acme')
                    ->check('input[value="Acme"]')
                    ->press('Apply');
            })
                ->waitUntilMissingText('Cola')
                ->assertSee('Apple')
                ->assertSee('Bread');
        });
    }

    #[Test]
    public function the_column_filter_is_kept_in_the_session_after_a_reload(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/admin/products')
                ->waitForText('PRD-003');

            $this->openColumnFilter($browser, 'Brand');

            $browser->within('.fcf-panel--open', fn (Browser $panel) => $panel->check('input[value="Acme"]')->press('Apply'))
                ->waitUntilMissingText('Cola')
                ->refresh()
                ->waitForText('PRD-001')
                ->assertDontSee('Cola');
        });
    }

    #[Test]
    public function the_column_filter_of_a_relation_only_lists_the_values_in_use(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/admin/products')
                ->waitForText('PRD-003');

            $this->openColumnFilter($browser, 'Category');

            $browser->within('.fcf-panel--open', function (Browser $panel) {
                $panel->assertSee('Food')
                    ->assertSee('Drink')
                    ->assertDontSee('Unused')
                    ->check('input[value="Drink"]')
                    ->press('Apply');
            })
                ->waitUntilMissingText('Apple')
                ->assertSee('Cola')
                ->assertDontSee('Bread');
        });
    }

    #[Test]
    public function the_column_filter_of_a_column_shows_formatted_labels(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/admin/products')
                ->waitForText('PRD-003');

            $this->openColumnFilter($browser, 'Weight');

            $browser->within('.fcf-panel--open', function (Browser $panel) {
                $panel->assertSee('150.500 gr')
                    ->assertSee('500.000 gr');
            });

            $browser->driver->getKeyboard()->sendKeys(WebDriverKeys::ESCAPE);
            $browser->waitUntilMissing('.fcf-panel--open');

            $this->openColumnFilter($browser, 'Active');

            $browser->within('.fcf-panel--open', function (Browser $panel) {
                $panel->assertSee('Not active')
                    ->assertSee('Active');
            });
        });
    }

    private function openColumnFilter(Browser $browser, string $label): void
    {
        $browser->script(<<<JS
            [...document.querySelectorAll('.fcf-header')]
                .find(header => header.querySelector('.fcf-header-label')?.textContent.trim() === '{$label}')
                .querySelector('.fcf-trigger')
                .click();
            JS);

        $browser->waitFor('.fcf-panel--open');
    }

    private function rowAction(Browser $browser, string $rowText, string $action): void
    {
        $browser->driver
            ->findElement(WebDriverBy::xpath("//tr[contains(@class, 'fi-ta-row')][contains(., '{$rowText}')]//*[@aria-label='{$action}']"))
            ->click();
    }
}
