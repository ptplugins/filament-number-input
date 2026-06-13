# Changelog

All notable changes to `ptplugins/filament-number-input` are documented here.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and this project adheres to [Semantic Versioning](https://semver.org/).

## [1.0.2] - 2026-06-13

### Changed
- Sharpened the package `description` (Packagist / listing abstract): now names Filament v3/v4/v5 and leads with the pain it removes — "no more broken SUM()s or cast errors from localized number strings."

## [1.0.1] - 2026-06-13

### Added
- Listing banner (`screenshot.png`, 2560×1440) for the filamentphp.com/plugins submission, plus a README hero image carrying `filament-hidden` so it shows on GitHub/Packagist but not on the Filament listing (where the banner already appears).

## [1.0.0] - 2026-06-13

### Added
- Initial public release under `ptplugins/filament-number-input` (MIT).
- `NumberInput` form field for FilamentPHP — a locale-aware numeric input that formats decimal and thousands separators in the browser while the model only ever stores a clean float.
- Single codebase across Filament **v3, v4, and v5** (`^3.0 || ^4.0 || ^5.0`). Verified against Filament v4.7.2 (`view:cache` compile).
- Two-layer design. **JS is primary:** only the parsed float (`rawValue`, entangled) crosses to the server; the localized text (`formattedValue`, `x-model`) never leaves the browser. **PHP is a safety net:** `normalizeToFloat()` on dehydrate + a validation rule catch values that bypass the browser (programmatic `$set()`, imports, seeders, paste). The safety net is idempotent for already-numeric values, so it never corrupts the clean float the JS layer produced. Same storage contract as the Pikaday date field.
- Configuration API:
  - `decimalSeparator()` / `thousandsSeparator()` — accept a string or closure.
  - `decimalPlaces()` — zero-padding applied on blur when no decimals were typed.
  - `european()` — preset for `,` decimal / `.` thousands (default).
  - `american()` — preset for `.` decimal / `,` thousands.
- Extends Filament's `TextInput`, so every `TextInput` method (affixes, `required()`, `disabled()`, …) keeps working.
