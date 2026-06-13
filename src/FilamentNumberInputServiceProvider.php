<?php

namespace PtPlugins\FilamentNumberInput;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FilamentNumberInputServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filament-number-input';

    public function configurePackage(Package $package): void
    {
        $package->name(static::$name)
            ->hasViews();
    }
}
