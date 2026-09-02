<?php

declare(strict_types=1);

namespace Tapp\FilamentCertificateBuilder\Filament\Resources\CertificateTemplates\Pages;

use Filament\Resources\Pages\CreateRecord;
use Tapp\FilamentCertificateBuilder\Filament\Resources\CertificateTemplates\CertificateTemplateResource;
use Tapp\FilamentCertificateBuilder\Support\CertificateLayout;

class CreateCertificateTemplate extends CreateRecord
{
    protected static string $resource = CertificateTemplateResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['token_set'] ??= CertificateLayout::defaultTokenSet();
        $data['layout'] = CertificateLayout::default(
            is_string($data['token_set']) ? $data['token_set'] : null,
        );

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }
}
