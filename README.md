# Filament Kompact

A set of helper functions and macros in building app using Filament in Laravel. Built on [Eloquent Kompact](https://github.com/jund-fauz/eloquent-kompact) and its [column naming convention](https://github.com/jund-fauz/eloquent-kompact#column-naming-convention).

```bash
composer require jund-fauz/filament-kompact
```

Requires PHP 8.3+, Laravel 13 and Filament 5. The service provider is registered through package discovery.

The column filters need [`zvizvi/filament-column-filters`](https://github.com/zvizvi/filament-column-filters). It is optional: without it, or while its plugin is not registered on the panel, columns get no filter.

```bash
composer require zvizvi/filament-column-filters
```

Register its plugin on the panel, because the plugin adds the `columnFilter()` method that this package calls:

```php
use Zvizvi\FilamentColumnFilters\FilamentColumnFiltersPlugin;

public function panel(Panel $panel): Panel
{
    return $panel->plugin(FilamentColumnFiltersPlugin::make());
}
```

- [Versioning](#versioning)
- [KompactTable](#kompacttable)
- [Timestamp columns](#timestamp-columns)
- [Column filters](#column-filters)
- [Macros](#macros)
- [Format](#format)
- [Testing](#testing)

## Versioning

This package follows [Semantic Versioning](https://semver.org). It is still in initial development (`0.x`): a minor release (`0.x.0`) may contain breaking changes, while a patch release (`0.x.y`) never does. Composer's `^0.x` constraint only allows patch updates, so require it as e.g. `^0.1`. The API is considered stable from `1.0.0`.

Classes and methods marked `@internal` are not covered by this promise and may change in any release.

## KompactTable

Extend `KompactTable` to build a table with sensible defaults, then call `table()` from the `configure()` method that a Filament resource uses:

```php
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use JundFauz\FilamentKompact\Concerns\KompactTable;

class ProductsTable extends KompactTable
{
    protected static array $statsColumns = ['name', 'brand', 'category.name'];

    public static function configure(Table $table): Table
    {
        return static::table($table);
    }

    protected static function getColumns(): array
    {
        return [
            TextColumn::make('name')->searchSortable(),
            TextColumn::make('brand'),
            TextColumn::make('category.name'),
        ];
    }
}
```

```php
// ProductResource
public static function table(Table $table): Table
{
    return ProductsTable::configure($table);
}
```

The table gets:

- your columns, followed by the [timestamp columns](#timestamp-columns);
- a [column filter](#column-filters) on every column in `$statsColumns` that has at least one value;
- a `TrashedFilter`;
- record actions: edit and delete for a record, force delete and restore for a trashed record, as icons with a tooltip. A view action is also added, hidden with CSS (turn it off with `getToggledViewAction()`);
- delete, restore and force delete bulk actions;
- filters, sort and search kept in the session.

### Options

| Property | Default | Used for |
|---|---|---|
| `$statsColumns` | `[]` | Columns that get a column filter |
| `$recordActionOnlyShowIcon` | `true` | Show the record actions as icons with a tooltip |
| `$showTimestamps` | `true` | Add the timestamp columns |
| `$showDeletedAt` | `true` | Add the `deleted_at` column |
| `$persistInSession` | `true` | Keep filters, sort and search in the session |

### Hooks

Override these methods to change a part of the table:

| Method | Default |
|---|---|
| `getColumns()` | `[]` |
| `getStatsColumn()` | `$statsColumns` |
| `getFilters()` | `[TrashedFilter::make()]` |
| `getRecordActions()` | Edit, delete, force delete, restore |
| `getToggledViewAction()` | `true`: add the hidden view action |
| `getToolbarActions()` | Delete, restore and force delete bulk actions |
| `configureColumnFilter(ColumnFilter $filter, Column $column)` | Nothing. Adjust each column filter, e.g. by `$column->getName()` |
| `getModifiedQuery()` | The query unchanged |

## Timestamp columns

```php
use JundFauz\FilamentKompact\Table\ColumnTimestamps;

$table->columns([
    TextColumn::make('name'),
    ...ColumnTimestamps::tableColumns(),        // created_at, updated_at, deleted_at
    // ...ColumnTimestamps::tableColumns(false) // without deleted_at
]);
```

The columns are formatted as `d-m-Y, H:i`, sortable and toggleable. `updated_at` and `deleted_at` are hidden by default. `created_at` gets a date column filter when the `zvizvi/filament-column-filters` plugin is registered on the panel.

## Column filters

`Filter::createForColumn()` adds a select column filter whose options are the distinct, non-empty values of the column:

```php
use JundFauz\FilamentKompact\Helper\Filter;

Filter::createForColumn(TextColumn::make('brand'));
TextColumn::make('brand')->createFilter(); // the same, as a macro
```

The options follow the column name:

| Column | Options |
|---|---|
| `active_is` | `Not active` and `Active` |
| `weight_gram`, `stock_pcs`, `tax_percentage` | Formatted with [Format](#format) |
| `status_enum` | The value of the enum |
| `category.name` | The names of the categories that are used by a record of the table, through a `BelongsTo`, `HasOne` or `HasMany` relation |

Pass the result of `Filter::getCount()` to skip a column that has no value. It counts the non-empty values of each column in one query:

```php
$stats = Filter::getCount(Product::class, 'brand', 'name');

TextColumn::make('brand')->createFilter($stats);
```

Other parameters of `createForColumn()`:

| Parameter | Used for |
|---|---|
| `$searchable` | Also make the column searchable |
| `$customConfiguration` | `fn (ColumnFilter $filter, Column $column)`, to adjust the filter |
| `$customFilterOptionConfiguration` | Change the options query of this relational column, see below |

### Global configuration

Run a callback on every column filter, e.g. in a service provider:

```php
Filter::configureUsing(function (ColumnFilter $filter, Column $column) {
    $filter->multiple();
});
```

Change how the options query of a relational column is built. Take `$query` and `$relationNames` by reference and reassign them; `$walk` is the default walk through the relations:

```php
Filter::resolveOptionQueryUsing(function (&$query, &$relationNames, string $columnName, Closure $walk) {
    if ($columnName === 'category.name') {
        $query = Product::query()->where('active_is', true);
    }
});
```

## Macros

| Macro | Does |
|---|---|
| `TextColumn::formatId(string $prefix, ?int $digit = 3)` | Pads the state with `0` and adds the prefix: `5` → `INV-005` |
| `TextEntry::formatId(string $prefix, ?int $digit = 3)` | The same, for an infolist |
| `TextColumn::searchSortable($sortableCondition = true, $searchableCondition = true)` | `searchable()` and `sortable()` |
| `Repeater::minOneItem()` | Starts with one item, requires at least one, and hides the delete button of the last item |
| `Column::createFilter(?object $stats = null)` | `Filter::createForColumn()`, when `zvizvi/filament-column-filters` is installed. Does nothing while its plugin is not registered on the panel |

```php
TextColumn::make('id')->formatId('INV-');
TextColumn::make('code')->formatId('P-', 5); // 42 → P-00042
```

## Format

Every formatter returns `-` for a blank value.

| Method | Example |
|---|---|
| `Format::gram()` | `1.2341` → `1.235 gr` (3 decimals, rounded up) |
| `Format::pcs()` | `1234567` → `1,234,567 pcs` |
| `Format::percentage()` | `12.5` → `12.5%` |
| `Format::carat()` | `24` → `24 K` |
| `Format::time()` | `08:30:59` → `08:30` |

## Testing

```bash
composer test
```

## License

MIT. See [LICENSE](LICENSE).
