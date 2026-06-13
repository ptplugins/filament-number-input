# Filament Number Input

> A locale-aware numeric input field for [FilamentPHP](https://filamentphp.com/) **v3, v4, and v5**. The user types and sees a localized number (European `12.345,67` or US `12,345.67`); your model always stores a clean float (`12345.67`).

Single codebase across all three Filament major versions — same field, same API.

<p align="center" class="filament-hidden">
  <a href="https://ptplugins.com/buy-us-a-beer"><img src="https://img.shields.io/badge/%F0%9F%8D%BA-Buy%20us%20a%20beer-yellow" alt="Buy us a beer"></a>
</p>

**🎯 [Try it live · ptplugins.com/demo/number-input](https://ptplugins.com/demo/number-input)** — type a number and watch the stored float update.

## The problem

Localized number formatting is one of those things that looks trivial and then quietly corrupts your data for a year.

If you let a localized string reach the database — `"12.345,67"` — everything downstream breaks:

- `SUM()` / `AVG()` and any SQL math return garbage or zero.
- `decimal` / `float` casts silently truncate at the first separator (`"12.345,67"` → `12.345` or `12`).
- Sorting is lexical, not numeric.
- The moment two users have different locales (`12.345,67` vs `12,345.67`), the same column holds two incompatible formats.

And the naive fix — "just format on the front end" — has its own trap: any value set **programmatically** (a seeder, an import, `$set('amount', '12,34')`, a copy/paste) never passes through the browser formatter, so the localized string slips straight into the model.

This field solves both ends. **The database only ever sees a plain float**, no matter how the value was entered — exactly the same contract as a well-behaved date picker that stores ISO `Y-m-d` while showing `d.m.Y`.

## How it works — two cooperating layers

```
        ┌─────────────────────────── browser ───────────────────────────┐
 user → │  formattedValue  (x-model, LOCAL Alpine state)  "12.345,67"    │
 types  │        │  parseNumber()                                        │
        │        ▼                                                       │
        │  rawValue  ($wire.entangle, the ONLY thing synced)  12345.67   │
        └────────────────────────────│──────────────────────────────────┘
                                      ▼
        ┌──────────────────────────── server ──────────────────────────┐
        │  normalizeToFloat()  — idempotent safety net                  │  → DB: 12345.67 (float)
        └───────────────────────────────────────────────────────────────┘
```

1. **Primary layer (JavaScript).** An Alpine component formats and parses as the user types. The localized text lives only in `formattedValue`, which is bound to the input via `x-model` and **never leaves the browser**. The only value entangled with the server is `rawValue` — already a parsed float. In the normal interactive flow, the server never even sees a localized string.

2. **Safety net (PHP).** `dehydrateStateUsing()` runs `normalizeToFloat()`, and a validation rule guards bad input. This layer exists for values that **bypass** the browser entirely — programmatic `$set()`, imports, seeders, paste into a non-rendered field. It is **idempotent for anything already numeric**: an int/float (or canonical numeric string) is returned unchanged, so it can never corrupt the clean float the JS layer produced. It only does work on a localized *string*. Both layers read the **same** separator configuration, so they can never disagree.

> Why two layers and not just one? JS alone leaks on programmatic writes; PHP alone means re-implementing locale parsing on the server and giving up live, in-field formatting. Together: live UX **and** a hard guarantee at the storage boundary.

## Installation

```bash
composer require ptplugins/filament-number-input
```

The package auto-discovers its service provider. There are no assets to publish — the Alpine logic ships inline with the field's Blade view.

## Quick start

```php
use PtPlugins\FilamentNumberInput\Fields\NumberInput;

NumberInput::make('price');
```

That's it. The default is the European convention (dot groups thousands, comma marks decimals). The bound model attribute will be a float.

## Configuration

### Presets

```php
NumberInput::make('price')->european(); // 12.345,67  (default)
NumberInput::make('price')->american(); // 12,345.67
```

### Custom separators

```php
NumberInput::make('price')
    ->decimalSeparator(',')
    ->thousandsSeparator(' ') // e.g. "12 345,67"  (French / SI style)
    ->decimalPlaces(2);
```

| Method | Default | Description |
| --- | --- | --- |
| `decimalSeparator(string\|Closure)` | `,` | Character shown between integer and decimal part |
| `thousandsSeparator(string\|Closure)` | `.` | Character grouping thousands |
| `decimalPlaces(int\|Closure)` | `2` | Zero-padding added on blur when the user typed no decimals (`12` → `12,00`) |
| `european()` | — | Shortcut for `,` decimal / `.` thousands |
| `american()` | — | Shortcut for `.` decimal / `,` thousands |

All four accept a closure, so separators can depend on the record or the authenticated user's locale.

`NumberInput` **extends Filament's `TextInput`**, so every `TextInput` method works unchanged — `->required()`, `->prefix('€')`, `->suffix('RSD')`, `->disabled()`, `->placeholder()`, affix icons and actions, `->live()`, and so on.

## Storage guarantee

Whatever the display format, the dehydrated state is a PHP `float`:

```php
NumberInput::make('price')->european(); // user sees 1.234,56 → model stores 1234.56
NumberInput::make('price')->american(); // user sees 1,234.56 → model stores 1234.56

// And directly, for imports/seeders, the same parsing is available:
NumberInput::make('price')->european()->normalizeToFloat('1.234,56'); // 1234.56
NumberInput::make('price')->american()->normalizeToFloat('1,234.56'); // 1234.56
NumberInput::make('price')->normalizeToFloat(1234.56);                 // 1234.56 (unchanged)
NumberInput::make('price')->normalizeToFloat('');                      // null
```

Unparseable input is returned untouched so the validation rule rejects it, rather than silently corrupting the stored value.

## Edge cases & gotchas (learned the hard way)

These are the real-world traps we hit running an earlier version of this field across a production Filament app. They're documented here so you don't have to rediscover them.

- **Programmatic `$set()` with a localized string.** `$set('amount', '12,34')` never runs the JS parser. Without the PHP safety net, that string lands in the database verbatim. With it, it's normalized on dehydrate. This is *the* reason the PHP layer exists.

- **The lone-dot ambiguity in European mode.** In EU mode a value like `"1.234"` (no decimal comma) is ambiguous: is it *one thousand two hundred thirty-four* (dot = thousands) or *1.234* (a native float)? Both the JS and PHP layers resolve it the **same** way — as a native float `1.234` — so a programmatically-set float survives the round trip intact. If you mean 1234, set the integer `1234`, not the string `"1.234"`.

- **Don't reach for `->mask()`.** An obvious-looking approach is a Filament money mask (`RawJs::make('$money(...)')`) plus a PHP `dehydrateStateUsing`. We shipped that first. It's brittle: the mask, the dehydrator and a `formatStateUsing` hook all have to agree, edits mid-string fight the mask cursor, and pasted/programmatic values slip past. This field replaces that whole dance with one Alpine component that owns formatting end to end. Just use `NumberInput`; don't add a mask on top.

- **`decimalPlaces` only pads on blur.** Typing `12` and tabbing away shows `12,00`. It does not force precision while typing, and it does not round — it's a display convenience, not a rounding policy. Round in your cast/mutator if you need fixed precision in storage.

- **Decimals shown ≠ precision stored.** The field stores the full parsed float. If you want the database value clamped to N decimals, do it in the model (`decimal:2` cast), not here.

## Filament v3 / v4 / v5

One codebase serves all three. The field extends `TextInput` (stable across versions) and the Blade view uses only the cross-version `<x-filament::input.wrapper>` / `<x-filament::input>` primitives — not the internal markup that Filament reshuffled between v3 and v4. State binding goes through `applyStateBindingModifiers`, so `->live()` / deferred behaviour follows whatever the host version does. Verified against Filament v4.7.2 (`view:cache` compile) in addition to v3.

## Requirements

- PHP 8.1+
- Filament 3.x, 4.x, or 5.x

## License

MIT — see [LICENSE.md](LICENSE.md).

---

Part of the [ptplugins.com](https://ptplugins.com) collection of FilamentPHP plugins.
