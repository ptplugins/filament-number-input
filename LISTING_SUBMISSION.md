# Filament Number Input — Listing Submission

Submit via **https://filamentphp.com/author** dashboard (not a PR).
Banner image to upload is `screenshot.png` (2560×1440) in this same folder.

---

## Fields

| Field | Value |
|---|---|
| **Name** | Filament Number Input |
| **Package (Packagist)** | `ptplugins/filament-number-input` |
| **Tagline** | Localized number input that always stores a clean float. |
| **Short description** | Locale-aware number input for FilamentPHP v3, v4, and v5. Users see `12.345,67` or `12,345.67` while your database always stores a clean float — no more broken SUM()s or cast errors from localized number strings. |
| **Repository** | https://github.com/ptplugins/filament-number-input |
| **Packagist** | https://packagist.org/packages/ptplugins/filament-number-input |
| **License** | MIT (Free) |
| **Categories** | Form Field / Form Component |
| **Keywords** | filament, number, currency, input, formatting, locale, form-field |
| **Thumbnail / Banner** | `screenshot.png` (this folder) |
| **Compatible with** | Filament 3, 4, 5 |

---

## Description

A locale-aware numeric input field for FilamentPHP. The user types and sees a localized number — European `12.345,67` or US `12,345.67`, or any custom separators — while your model **always stores a clean float** (`12345.67`). Drop-in `NumberInput` that extends Filament's `TextInput`, so every affix, validation and state method keeps working. Single package for Filament 3, 4, and 5.

Localization is handled entirely on the front end: only the parsed float is synced to the server, so `SUM()` / `AVG()`, numeric casts and sorting never break on a localized string. A PHP safety net catches values set programmatically (`$set()`, imports, seeders) so the database never sees anything but a float — same storage contract as a well-behaved date field that stores ISO `Y-m-d` while showing `d.m.Y`.

---

## Install (for the listing instructions)

```bash
composer require ptplugins/filament-number-input
```

```php
use PtPlugins\FilamentNumberInput\Fields\NumberInput;

NumberInput::make('price')->european(); // 12.345,67 → stores 1234.56
NumberInput::make('price')->american(); // 12,345.67 → stores 1234.56
NumberInput::make('price')
    ->decimalSeparator(',')
    ->thousandsSeparator(' ')            // 12 345,67
    ->decimalPlaces(2);
```

---

## Status

- ⏳ Packagist: submit `https://github.com/ptplugins/filament-number-input` at packagist.org, then enable the GitHub → Packagist webhook.
- ✅ GitHub release: https://github.com/ptplugins/filament-number-input/releases/tag/1.0.0
- ✅ Banner rendered: `screenshot.png` (2560×1440).
