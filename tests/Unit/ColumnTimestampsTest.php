<?php

namespace JundFauz\FilamentKompact\Tests\Unit;

use Filament\Tables\Columns\TextColumn;
use JundFauz\FilamentKompact\Table\ColumnTimestamps;
use JundFauz\FilamentKompact\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Zvizvi\FilamentColumnFilters\FilamentColumnFilters;
use Zvizvi\FilamentColumnFilters\Filters\DateColumnFilter;

class ColumnTimestampsTest extends TestCase
{
    private function names(array $columns): array
    {
        return array_map(fn (TextColumn $column) => $column->getName(), $columns);
    }

    #[Test]
    public function it_returns_the_timestamp_columns_with_deleted_at_by_default(): void
    {
        $this->assertSame(['created_at', 'updated_at', 'deleted_at'], $this->names(ColumnTimestamps::tableColumns()));
    }

    #[Test]
    public function it_leaves_out_deleted_at_when_asked(): void
    {
        $this->assertSame(['created_at', 'updated_at'], $this->names(ColumnTimestamps::tableColumns(false)));
    }

    #[Test]
    public function every_column_is_sortable_and_toggleable(): void
    {
        foreach (ColumnTimestamps::tableColumns() as $column) {
            $this->assertTrue($column->isSortable(), $column->getName());
            $this->assertTrue($column->isToggleable(), $column->getName());
        }
    }

    #[Test]
    public function only_created_at_is_visible_by_default(): void
    {
        [$createdAt, $updatedAt, $deletedAt] = ColumnTimestamps::tableColumns();

        $this->assertFalse($createdAt->isToggledHiddenByDefault());
        $this->assertTrue($updatedAt->isToggledHiddenByDefault());
        $this->assertTrue($deletedAt->isToggledHiddenByDefault());
    }

    #[Test]
    public function created_at_has_a_date_column_filter(): void
    {
        [$createdAt, $updatedAt] = ColumnTimestamps::tableColumns();

        $this->assertInstanceOf(DateColumnFilter::class, FilamentColumnFilters::getColumnFilter($createdAt));
        $this->assertNull(FilamentColumnFilters::getColumnFilter($updatedAt));
    }
}
