# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.2.1] - 2026-11-10

### Fixed

- `Repeater::minOneItem()` names the parameter of its delete-button closure `$component`, which Filament injects. Before, `$repeater` could not be resolved and rendering the delete button threw a `BindingResolutionException`, so the button of the last item was never hidden.

### Tests

- Added a test that the delete button of `minOneItem()` is hidden with one item and shown with two.

## [0.2.0] - 2026-11-09

### Changed

- **Breaking:** requires Eloquent Kompact `^1.0` instead of `^0.100`. An app that requires Eloquent Kompact `0.x` itself has to upgrade it too, see its [changelog](https://github.com/jund-fauz/eloquent-kompact/blob/main/CHANGELOG.md#100---2026-11-09) (e.g. the `fillable.exclude_column_by` config).
- `Filter` uses `ColumnCheck`, the new name of Eloquent Kompact's `Checker`.

## [0.1.1] - 2026-11-09

### Fixed

- `ColumnTimestamps`, `KompactTable` and `Filter::createForColumn()` (and so `Column::createFilter()`) check `Column::hasMacro('columnFilter')` instead of whether `zvizvi/filament-column-filters` is installed. Before, a table threw a `BadMethodCallException` when the package was installed but its plugin was not registered on the panel; now the column filters are left out.

### Tests

- Added tests for tables built without the column filter plugin.

## [0.1.0] - 2026-11-09

First release.

### Added

- `KompactTable`: a base class that builds a Filament table with the timestamp columns, a `TrashedFilter`, icon-only record actions (view, edit, delete, force delete, restore) that follow the trashed state of the record, bulk actions, and a column filter for every column listed in `$statsColumns`.
- `ColumnTimestamps::tableColumns()`: sortable and toggleable `created_at`, `updated_at` and `deleted_at` columns, with a date column filter on `created_at`.
- `Filter::createForColumn()`: a select column filter whose options are the distinct values of the column, also through `BelongsTo` and `HasOne`/`HasMany` relations (`category.name`). Labels are formatted by the column token (`_gram`, `_pcs`, `_percentage`, `_enum`, `_is`).
- `Filter::getCount()`: counts the non-empty values of each column, so a column without values gets no filter.
- `Filter::configureUsing()` and `Filter::resolveOptionQueryUsing()`: global hooks for every column filter and for the options query of a relational column.
- Macros `TextColumn::formatId()`, `TextEntry::formatId()`, `TextColumn::searchSortable()`, `Repeater::minOneItem()` and `Column::createFilter()`.
- `Format`: `gram()`, `pcs()`, `percentage()`, `carat()` and `time()`, which return `-` for a blank value.

### Fixed

- `KompactTable` returns its record actions and columns with `all()` instead of `toArray()`, which serialized every action without a record and threw a `TypeError`.
- `Filter::getCount()` works with Eloquent Kompact 0.99+, where `collect()` without a value throws.
- Filter options of a decimal column keep their value (`150.5`) instead of being truncated to an `int` array key (`150`).
- `Format::gram()` keeps the decimals of a `float` (`0.5` gave `0.000 gr`).
- `ColumnTimestamps` only adds the date column filter when `zvizvi/filament-column-filters` is installed.

### Tests

- Added tests for `KompactTable`, `ColumnTimestamps`, `Filter`, the macros and `Format`, and a GitHub Actions workflow on PHP 8.3–8.5 with Laravel 13.

[Unreleased]: https://github.com/jund-fauz/filament-kompact/compare/v0.2.1...HEAD
[0.2.1]: https://github.com/jund-fauz/filament-kompact/compare/v0.2.0...v0.2.1
[0.2.0]: https://github.com/jund-fauz/filament-kompact/compare/v0.1.1...v0.2.0
[0.1.1]: https://github.com/jund-fauz/filament-kompact/compare/v0.1.0...v0.1.1
[0.1.0]: https://github.com/jund-fauz/filament-kompact/releases/tag/v0.1.0
