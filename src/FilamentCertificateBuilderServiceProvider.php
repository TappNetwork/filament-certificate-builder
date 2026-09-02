<?php

declare(strict_types=1);

namespace Tapp\FilamentCertificateBuilder;

use Filament\Support\Assets\Asset;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Facades\FilamentIcon;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Tapp\FilamentCertificateBuilder\Commands\FilamentCertificateBuilderCommand;
use Tapp\FilamentCertificateBuilder\Livewire\CertificateLayoutDesigner;
use Tapp\FilamentCertificateBuilder\Testing\TestsFilamentCertificateBuilder;

class FilamentCertificateBuilderServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filament-certificate-builder';

    public static string $viewNamespace = 'filament-certificate-builder';

    public function configurePackage(Package $package): void
    {
        $package->name(static::$name)
            ->hasConfigFile('certificate-builder')
            ->hasViews(static::$viewNamespace)
            ->hasMigration('create_certificate_templates_table')
            ->hasCommands($this->getCommands())
            ->hasInstallCommand(function (InstallCommand $command): void {
                $command
                    ->publishConfigFile()
                    ->publishMigrations()
                    ->askToRunMigrations()
                    ->askToStarRepoOnGitHub('TappNetwork/filament-certificate-builder');
            });

        if (file_exists($package->basePath('/../resources/lang'))) {
            $package->hasTranslations();
        }
    }

    public function packageBooted(): void
    {
        Livewire::component(
            'tapp.filament-certificate-builder.certificate-layout-designer',
            CertificateLayoutDesigner::class,
        );

        FilamentAsset::register(
            $this->getAssets(),
            $this->getAssetPackageName()
        );

        FilamentAsset::registerScriptData(
            $this->getScriptData(),
            $this->getAssetPackageName()
        );

        FilamentIcon::register($this->getIcons());

        Testable::mixin(new TestsFilamentCertificateBuilder);
    }

    protected function getAssetPackageName(): ?string
    {
        return 'tapp/filament-certificate-builder';
    }

    /**
     * @return array<Asset>
     */
    protected function getAssets(): array
    {
        return [];
    }

    /**
     * @return array<class-string>
     */
    protected function getCommands(): array
    {
        return [
            FilamentCertificateBuilderCommand::class,
        ];
    }

    /**
     * @return array<string>
     */
    protected function getIcons(): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    protected function getScriptData(): array
    {
        return [];
    }
}
