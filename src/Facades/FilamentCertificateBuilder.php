<?php

namespace Tapp\FilamentCertificateBuilder\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Tapp\FilamentCertificateBuilder\FilamentCertificateBuilder
 */
class FilamentCertificateBuilder extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Tapp\FilamentCertificateBuilder\FilamentCertificateBuilder::class;
    }
}
