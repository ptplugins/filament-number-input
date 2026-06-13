<?php

namespace PtPlugins\FilamentNumberInput\Fields;

use Closure;
use Filament\Forms\Components\TextInput;

/**
 * Locale-aware numeric input for Filament.
 *
 * Localization is a front-end concern. The Alpine view (number-input.blade.php) shows the
 * value with localized separators (European "12.345,67" or US "12,345.67") via a local
 * `formattedValue` (x-model), while only `rawValue` — a parsed float — is entangled and
 * synced to the server. The model therefore receives a clean float ("12345.67"), so SQL
 * SUM()/AVG() and numeric casts keep working. Same contract as the Pikaday date field.
 *
 * Two layers cooperate:
 *  - PRIMARY (JS): the Alpine view parses to a float as the user types, so the entangled
 *    state the server sees is already a float in the normal interactive flow.
 *  - SAFETY NET (PHP): {@see normalizeToFloat()} runs on dehydrate, plus a validation rule.
 *    It is IDEMPOTENT for everything the JS layer emits — a numeric value (int/float) is
 *    returned unchanged — so it never corrupts a clean float. It only does work for a
 *    localized STRING that bypassed the parser entirely (programmatic `$set('x', '12,34')`,
 *    imports, seeders, copy/paste into a non-rendered field). JS and PHP read the SAME
 *    separator getters, so the two layers can never disagree on how to parse.
 */
class NumberInput extends TextInput
{
    protected string $view = 'filament-number-input::number-input';

    protected string|Closure $decimalSeparator = ',';

    protected string|Closure $thousandsSeparator = '.';

    protected int|Closure $decimalPlaces = 2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rule(fn (NumberInput $component): Closure => function (string $attribute, mixed $value, Closure $fail) use ($component): void {
            if ($value === null || $value === '') {
                return;
            }

            if (is_numeric($value)) {
                return;
            }

            if (is_string($value) && is_numeric($component->normalizeToFloat($value))) {
                return;
            }

            $fail(__('validation.numeric'));
        });

        $this->dehydrateStateUsing(fn (mixed $state, NumberInput $component): mixed => $component->normalizeToFloat($state));
    }

    public function decimalSeparator(string|Closure $separator): static
    {
        $this->decimalSeparator = $separator;

        return $this;
    }

    public function thousandsSeparator(string|Closure $separator): static
    {
        $this->thousandsSeparator = $separator;

        return $this;
    }

    public function decimalPlaces(int|Closure $places): static
    {
        $this->decimalPlaces = $places;

        return $this;
    }

    /**
     * European preset: dot groups thousands, comma marks decimals (e.g. "12.345,67").
     */
    public function european(): static
    {
        return $this->decimalSeparator(',')->thousandsSeparator('.');
    }

    /**
     * US/UK preset: comma groups thousands, dot marks decimals (e.g. "12,345.67").
     */
    public function american(): static
    {
        return $this->decimalSeparator('.')->thousandsSeparator(',');
    }

    public function getDecimalSeparator(): string
    {
        return (string) $this->evaluate($this->decimalSeparator);
    }

    public function getThousandsSeparator(): string
    {
        return (string) $this->evaluate($this->thousandsSeparator);
    }

    public function getDecimalPlaces(): int
    {
        return (int) $this->evaluate($this->decimalPlaces);
    }

    /**
     * Normalize a value into a float the database can store.
     *
     * IDEMPOTENT for anything already numeric (int/float, or a plain numeric string like
     * "1234.56") — it is returned as a float untouched, which is why it never harms the
     * clean float the JS layer produces. A localized string ("1.234,56") is parsed using
     * the configured separators. Null/empty become null. Anything that still cannot be
     * interpreted is returned untouched so the validation rule can reject it, rather than
     * silently corrupting the stored value.
     */
    public function normalizeToFloat(mixed $state): mixed
    {
        if ($state === null || $state === '') {
            return null;
        }

        if (is_numeric($state)) {
            return (float) $state;
        }

        if (is_string($state)) {
            $normalized = str_replace($this->getThousandsSeparator(), '', $state);
            $normalized = str_replace($this->getDecimalSeparator(), '.', $normalized);

            if (is_numeric($normalized)) {
                return (float) $normalized;
            }
        }

        return $state;
    }
}
