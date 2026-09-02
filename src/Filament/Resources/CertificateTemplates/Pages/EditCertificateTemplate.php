<?php

declare(strict_types=1);

namespace Tapp\FilamentCertificateBuilder\Filament\Resources\CertificateTemplates\Pages;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Route;
use Tapp\FilamentCertificateBuilder\Filament\Resources\CertificateTemplates\CertificateTemplateResource;
use Tapp\FilamentCertificateBuilder\Livewire\CertificateLayoutDesigner;

class EditCertificateTemplate extends EditRecord
{
    protected static string $resource = CertificateTemplateResource::class;

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getFormContentComponent(),
                Section::make('Certificate layout')
                    ->description('Drag elements to customize how this certificate looks. Use Save layout in the designer to persist position changes.')
                    ->schema([
                        Livewire::make(
                            CertificateLayoutDesigner::class,
                            fn (): array => [
                                'template' => $this->getRecord(),
                            ],
                        )->key('certificate-layout-designer-' . $this->getRecord()->getKey()),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    protected function getHeaderActions(): array
    {
        $actions = [];
        $previewRoute = config('certificate-builder.preview_template_route');

        if (is_string($previewRoute) && $previewRoute !== '' && Route::has($previewRoute)) {
            $actions[] = Action::make('preview')
                ->label('Preview with sample data')
                ->icon('heroicon-o-eye')
                ->url(fn (): string => route($previewRoute, $this->getRecord()))
                ->openUrlInNewTab();
        }

        $actions[] = DeleteAction::make();

        return $actions;
    }
}
