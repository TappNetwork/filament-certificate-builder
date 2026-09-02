<?php

declare(strict_types=1);

namespace Tapp\FilamentCertificateBuilder;

use Filament\Contracts\Plugin;
use Filament\Panel;

class FilamentCertificateBuilderPlugin implements Plugin
{
    public function getId(): string
    {
        return 'filament-certificate-builder';
    }

    public function register(Panel $panel): void
    {
        $resources = config('certificate-builder.resources', []);

        if (is_array($resources) && $resources !== []) {
            $panel->resources($resources);
        }
    }

    public function boot(Panel $panel): void {}

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());

        return $plugin;
    }
}
