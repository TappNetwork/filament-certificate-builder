<?php

declare(strict_types=1);

namespace Tapp\FilamentCertificateBuilder\Filament\Resources\CertificateTemplates\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Tapp\FilamentCertificateBuilder\Filament\Resources\CertificateTemplates\CertificateTemplateResource;

class ListCertificateTemplates extends ListRecords
{
    protected static string $resource = CertificateTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
