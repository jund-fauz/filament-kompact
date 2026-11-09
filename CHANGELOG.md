# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

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

[Unreleased]: https://github.com/jund-fauz/filament-kompact/compare/v0.1.0...HEAD
[0.1.0]: https://github.com/jund-fauz/filament-kompact/releases/tag/v0.1.0
