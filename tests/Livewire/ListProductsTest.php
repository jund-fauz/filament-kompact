<?php

namespace JundFauz\FilamentKompact\Tests\Livewire;

use Filament\Actions\Action;
use Filament\Actions\DeleteBulkAction;
use JundFauz\FilamentKompact\Tests\Fixtures\Filament\Pages\ListProducts;
use JundFauz\FilamentKompact\Tests\PanelTestCase;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;

/**
 * KompactTable, the column macros and the column filters inside a Filament resource page
 */
class ListProductsTest extends PanelTestCase
{
    private function list(): Testable
    {
        return Livewire::test(ListProducts::class);
    }

    #[Test]
    public function it_lists_the_records_that_are_not_trashed(): void
    {
        $this->list()
            ->assertOk()
            ->assertCanSeeTableRecords([$this->products['apple'], $this->products['bread'], $this->products['cola']])
            ->assertCanNotSeeTableRecords([$this->products['water']])
            ->assertCountTableRecords(3);
    }

    #[Test]
    public function format_id_formats_the_id_column(): void
    {
        $this->list()
            ->assertTableColumnFormattedStateSet('id', 'PRD-001', $this->products['apple'])
            ->assertTableColumnFormattedStateSet('id', 'PRD-003', $this->products['cola']);
    }

    #[Test]
    public function search_sortable_makes_the_column_searchable(): void
    {
        $this->list()
            ->searchTable('Bre')
            ->assertCanSeeTableRecords([$this->products['bread']])
            ->assertCanNotSeeTableRecords([$this->products['apple'], $this->products['cola']]);
    }

    #[Test]
    public function search_sortable_makes_the_column_sortable(): void
    {
        $records = [$this->products['apple'], $this->products['bread'], $this->products['cola']];

        $this->list()
            ->sortTable('name')
            ->assertCanSeeTableRecords($records, inOrder: true)
            ->sortTable('name', 'desc')
            ->assertCanSeeTableRecords(array_reverse($records), inOrder: true);
    }

    #[Test]
    public function the_timestamp_columns_are_added_and_only_created_at_is_shown(): void
    {
        $this->list()
            ->assertTableColumnExists('created_at')
            ->assertTableColumnExists('updated_at')
            ->assertTableColumnExists('deleted_at')
            ->assertCanRenderTableColumn('created_at')
            ->assertCanNotRenderTableColumn('updated_at')
            ->assertCanNotRenderTableColumn('deleted_at');
    }

    #[Test]
    public function the_timestamp_columns_are_sortable(): void
    {
        $this->products['cola']->forceFill(['created_at' => now()->subDay()])->save();

        $this->list()
            ->sortTable('created_at')
            ->assertCanSeeTableRecords([$this->products['cola'], $this->products['apple']], inOrder: true);
    }

    #[Test]
    public function record_actions_follow_the_trashed_state(): void
    {
        $apple = $this->products['apple'];

        $this->list()
            ->assertTableActionVisible('view', $apple)
            ->assertTableActionVisible('edit', $apple)
            ->assertTableActionVisible('delete', $apple)
            ->assertTableActionHidden('restore', $apple)
            ->assertTableActionHidden('forceDelete', $apple)
            ->filterTable('trashed', false)
            ->assertCanSeeTableRecords([$this->products['water']])
            ->assertCanNotSeeTableRecords([$apple])
            ->assertTableActionHidden('edit', $this->products['water'])
            ->assertTableActionHidden('delete', $this->products['water'])
            ->assertTableActionVisible('restore', $this->products['water'])
            ->assertTableActionVisible('forceDelete', $this->products['water']);
    }

    #[Test]
    public function record_actions_only_show_an_icon_with_a_tooltip(): void
    {
        foreach (['view', 'edit', 'delete', 'forceDelete', 'restore'] as $name) {
            $action = $this->tableAction($name);

            $this->assertTrue($action->isLabelHidden(), $name);
            $this->assertSame($action->getLabel(), $action->getTooltip(), $name);
        }
    }

    #[Test]
    public function the_delete_action_soft_deletes_the_record(): void
    {
        $this->list()->callTableAction('delete', $this->products['apple']);

        $this->assertSoftDeleted($this->products['apple']);
    }

    #[Test]
    public function the_restore_and_force_delete_actions_handle_a_trashed_record(): void
    {
        $this->list()
            ->filterTable('trashed', false)
            ->callTableAction('restore', $this->products['water']);

        $this->assertNotSoftDeleted($this->products['water']);

        $this->products['water']->delete();

        $this->list()
            ->filterTable('trashed', false)
            ->callTableAction('forceDelete', $this->products['water']);

        $this->assertModelMissing($this->products['water']);
    }

    #[Test]
    public function the_bulk_actions_delete_restore_and_force_delete(): void
    {
        $this->list()
            ->assertTableBulkActionExists('delete')
            ->assertTableBulkActionExists('restore')
            ->assertTableBulkActionExists('forceDelete')
            ->callTableBulkAction(DeleteBulkAction::class, [$this->products['apple'], $this->products['bread']]);

        $this->assertSoftDeleted($this->products['apple']);
        $this->assertSoftDeleted($this->products['bread']);
        $this->assertNotSoftDeleted($this->products['cola']);
    }

    #[Test]
    public function every_stats_column_gets_a_column_filter(): void
    {
        $list = $this->list();

        foreach (['cf_name', 'cf_brand', 'cf_category_name', 'cf_active_is', 'cf_weight_gram'] as $filter) {
            $list->assertTableFilterExists($filter);
        }

        $list->assertTableFilterExists('trashed');
    }

    #[Test]
    public function the_column_filter_of_a_column_filters_the_records(): void
    {
        $this->list()
            ->filterTable('cf_brand', 'Acme')
            ->assertCanSeeTableRecords([$this->products['apple'], $this->products['bread']])
            ->assertCanNotSeeTableRecords([$this->products['cola']]);
    }

    #[Test]
    public function the_column_filter_of_a_relation_filters_the_records(): void
    {
        $this->list()
            ->filterTable('cf_category_name', 'Drink')
            ->assertCanSeeTableRecords([$this->products['cola']])
            ->assertCanNotSeeTableRecords([$this->products['apple'], $this->products['bread']]);
    }

    #[Test]
    public function the_column_filter_of_a_boolean_column_filters_the_records(): void
    {
        $this->list()
            ->filterTable('cf_active_is', '1')
            ->assertCanSeeTableRecords([$this->products['apple']])
            ->assertCanNotSeeTableRecords([$this->products['bread'], $this->products['cola']]);
    }

    #[Test]
    public function the_column_filter_of_a_gram_column_filters_by_the_raw_value(): void
    {
        $this->list()
            ->filterTable('cf_weight_gram', '150.5')
            ->assertCanSeeTableRecords([$this->products['apple']])
            ->assertCanNotSeeTableRecords([$this->products['bread'], $this->products['cola']]);
    }

    #[Test]
    public function the_created_at_column_has_a_date_column_filter(): void
    {
        $this->products['cola']->forceFill(['created_at' => now()->subWeek()])->save();

        $this->list()
            ->assertTableFilterExists('cf_created_at')
            ->filterTable('cf_created_at', ['from' => now()->subDay()->toDateString()])
            ->assertCanSeeTableRecords([$this->products['apple'], $this->products['bread']])
            ->assertCanNotSeeTableRecords([$this->products['cola']]);
    }

    private function tableAction(string $name): Action
    {
        $actions = collect(Livewire::test(ListProducts::class)->instance()->getTable()->getRecordActions());

        return $actions->first(fn ($action) => $action->getName() === $name);
    }
}
