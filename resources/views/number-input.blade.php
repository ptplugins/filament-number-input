@php
    $datalistOptions = $getDatalistOptions();
    $extraAlpineAttributes = $getExtraAlpineAttributes();
    $id = $getId();
    $isConcealed = $isConcealed();
    $isDisabled = $isDisabled();
    $isPasswordRevealable = $isPasswordRevealable();
    $isPrefixInline = $isPrefixInline();
    $isSuffixInline = $isSuffixInline();
    $mask = $getMask();
    $prefixActions = $getPrefixActions();
    $prefixIcon = $getPrefixIcon();
    $prefixLabel = $getPrefixLabel();
    $suffixActions = $getSuffixActions();
    $suffixIcon = $getSuffixIcon();
    $suffixLabel = $getSuffixLabel();
    $statePath = $getStatePath();

    $xDataTemplate = <<<'XDATA'
    {
        rawValue: $wire.__ENTANGLE__,
        formattedValue: '',
        _userTyping: false,
        decimalSep: '__DECIMAL__',
        thousandsSep: '__THOUSANDS__',
        places: __PLACES__,
        init() {
            this.formattedValue = this.addSuffixIfNeeded(this.formatNumber(this.rawValue))
            this.$watch('rawValue', (value) => {
                if (!this._userTyping) {
                    this.formattedValue = this.addSuffixIfNeeded(this.formatNumber(value));
                }
            });
        },
        formatNumber(value) {
            if (value == null || value == '') return '';
            if (typeof value == 'string') value = this.parseNumber(value);
            if (typeof value != 'number' || isNaN(value)) return '';
            const parts = value.toString().split('.');
            parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, this.thousandsSep);
            return parts.join(this.decimalSep);
        },
        parseNumber(value) {
            if (value === 0) return 0;
            if (!value) return null;
            value = String(value);
            if (value.indexOf(this.decimalSep) !== -1) {
                // Value carries our decimal separator → strip grouping, swap decimal for a dot.
                return parseFloat(value.split(this.thousandsSep).join('').replace(this.decimalSep, '.'));
            }
            if (this.thousandsSep === '.') {
                // No decimal separator and a dot groups thousands: a lone dot is ambiguous
                // with a JS-native float, so parse directly to preserve programmatic values.
                return parseFloat(value);
            }
            // Grouping separator is safe to strip (e.g. a comma) before parsing.
            return parseFloat(value.split(this.thousandsSep).join(''));
        },
        addSuffixIfNeeded(fv) {
            if (this.places > 0 && fv.length > 0 && fv.indexOf(this.decimalSep) === -1) {
                fv += this.decimalSep + '0'.repeat(this.places);
            }
            return fv;
        },
    }
    XDATA;
    $xData = str_replace(
        ['__ENTANGLE__', '__DECIMAL__', '__THOUSANDS__', '__PLACES__'],
        [
            $applyStateBindingModifiers("\$entangle('{$statePath}')"),
            e($getDecimalSeparator()),
            e($getThousandsSeparator()),
            (int) $getDecimalPlaces(),
        ],
        $xDataTemplate
    );

    if ($isPasswordRevealable) {
        $type = null;
    } elseif (filled($mask)) {
        $type = 'text';
    } else {
        $type = $getType();
    }
@endphp

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <x-filament::input.wrapper
        :disabled="$isDisabled"
        :inline-prefix="$isPrefixInline"
        :inline-suffix="$isSuffixInline"
        :prefix="$prefixLabel"
        :prefix-actions="$prefixActions"
        :prefix-icon="$prefixIcon"
        :prefix-icon-color="$getPrefixIconColor()"
        :suffix="$suffixLabel"
        :suffix-actions="$suffixActions"
        :suffix-icon="$suffixIcon"
        :suffix-icon-color="$getSuffixIconColor()"
        :valid="! $errors->has($statePath)"
        :x-data="$xData"
        :attributes="
            \Filament\Support\prepare_inherited_attributes($getExtraAttributeBag())
                ->class(['fi-fo-text-input overflow-hidden'])
        "
    >
        <x-filament::input
            :attributes="
                \Filament\Support\prepare_inherited_attributes($getExtraInputAttributeBag())
                    ->merge($extraAlpineAttributes, escape: false)
                    ->merge([
                        'autocapitalize' => $getAutocapitalize(),
                        'autocomplete' => $getAutocomplete(),
                        'autofocus' => $isAutofocused(),
                        'disabled' => $isDisabled,
                        'id' => $id,
                        'inlinePrefix' => $isPrefixInline && (count($prefixActions) || $prefixIcon || filled($prefixLabel)),
                        'inlineSuffix' => $isSuffixInline && (count($suffixActions) || $suffixIcon || filled($suffixLabel)),
                        'inputmode' => $getInputMode(),
                        'list' => $datalistOptions ? $id . '-list' : null,
                        'max' => (! $isConcealed) ? $getMaxValue() : null,
                        'maxlength' => (! $isConcealed) ? $getMaxLength() : null,
                        'min' => (! $isConcealed) ? $getMinValue() : null,
                        'minlength' => (! $isConcealed) ? $getMinLength() : null,
                        'placeholder' => $getPlaceholder(),
                        'readonly' => $isReadOnly(),
                        'required' => $isRequired() && (! $isConcealed),
                        'step' => $getStep(),
                        'type' => $type,
                        'x-model' => 'formattedValue',
                        'x-on:focus' => '_userTyping = true',
                        'x-on:input' => 'rawValue = parseNumber($event.target.value)',
                        'x-on:blur' => '_userTyping = false; formattedValue = addSuffixIfNeeded(formatNumber(rawValue))',
                        'x-bind:type' => $isPasswordRevealable ? 'isPasswordRevealed ? \'text\' : \'password\'' : null,
                        'x-mask' . ($mask instanceof \Filament\Support\RawJs ? ':dynamic' : '') => filled($mask) ? $mask : null,
                    ], escape: false)
                    ->class([
                        '[&::-ms-reveal]:hidden' => $isPasswordRevealable,
                    ])
            "
        />
    </x-filament::input.wrapper>

    @if ($datalistOptions)
        <datalist id="{{ $id }}-list">
            @foreach ($datalistOptions as $option)
                <option value="{{ $option }}" />
            @endforeach
        </datalist>
    @endif
</x-dynamic-component>
