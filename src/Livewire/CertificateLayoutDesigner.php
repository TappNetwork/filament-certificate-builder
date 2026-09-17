<?php

declare(strict_types=1);

namespace Tapp\FilamentCertificateBuilder\Livewire;

use Filament\Notifications\Notification;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Tapp\FilamentCertificateBuilder\Models\CertificateTemplate;
use Tapp\FilamentCertificateBuilder\Support\CertificateLayout;

class CertificateLayoutDesigner extends Component
{
    public CertificateTemplate $template;

    /** @var array<int, array<string, mixed>> */
    public array $elements = [];

    public int $signatureCount = CertificateLayout::DEFAULT_SIGNATURE_COUNT;

    /** @var array{style: string, color: string, width: int, inner_color: string, inner_width: int, inner_inset: int, gradient: string} */
    public array $border = [];

    /** @var array{enabled: bool, height: int, background_color: string, background_size: string, background_position: string, title_bind: string, title: string, title_color: string, title_size: int, title_transform: string, subtitle: string, subtitle_color: string, subtitle_size: int} */
    public array $header = [];

    public ?string $selectedId = null;

    public function mount(CertificateTemplate $template): void
    {
        $this->template = $template;

        $layout = $template->resolvedLayout();
        $this->elements = $layout['elements'];
        $this->signatureCount = $layout['signature_count'];
        $this->border = $layout['border'];
        $this->header = $layout['header'];
    }

    public function selectElement(?string $id): void
    {
        $this->selectedId = $id;
    }

    public function setSignatureCount(int | string $count): void
    {
        $this->signatureCount = max(0, min(CertificateLayout::MAX_SIGNATURES, (int) $count));
        $this->elements = CertificateLayout::syncSignatureElements($this->elements, $this->signatureCount);

        if ($this->selectedId !== null && str_starts_with($this->selectedId, 'signature_')) {
            $stillExists = collect($this->elements)->contains(
                fn (array $element): bool => ($element['id'] ?? null) === $this->selectedId
            );

            if (! $stillExists) {
                $this->selectedId = null;
            }
        }
    }

    /**
     * @param  array{x?: int|float, y?: int|float, w?: int|float, h?: int|float}  $position
     */
    public function updateElementPosition(string $id, array $position): void
    {
        foreach ($this->elements as $index => $element) {
            if (($element['id'] ?? null) !== $id) {
                continue;
            }

            if (array_key_exists('x', $position)) {
                $this->elements[$index]['x'] = (int) max(0, $position['x']);
            }

            if (array_key_exists('y', $position)) {
                $this->elements[$index]['y'] = (int) max(0, $position['y']);
            }

            if (array_key_exists('w', $position)) {
                $this->elements[$index]['w'] = (int) max(20, $position['w']);
            }

            if (array_key_exists('h', $position)) {
                $this->elements[$index]['h'] = (int) max(20, $position['h']);
            }

            break;
        }
    }

    public function updateSelectedElementProperty(string $property, mixed $value): void
    {
        if ($this->selectedId === null) {
            return;
        }

        foreach ($this->elements as $index => $element) {
            if (($element['id'] ?? null) !== $this->selectedId) {
                continue;
            }

            if (in_array($property, ['fontSize', 'x', 'y', 'w', 'h'], true)) {
                $this->elements[$index][$property] = (int) $value;
            } elseif ($property === 'visible') {
                $this->elements[$index][$property] = filter_var($value, FILTER_VALIDATE_BOOLEAN);
            } else {
                $this->elements[$index][$property] = $value;
            }

            break;
        }
    }

    public function save(): void
    {
        $this->authorize('update', $this->template);

        $this->elements = $this->normalizedElements();

        $this->template->update([
            'layout' => [
                'width' => CertificateLayout::WIDTH,
                'height' => CertificateLayout::HEIGHT,
                'signature_count' => $this->signatureCount,
                'border' => CertificateLayout::normalizeBorder($this->border),
                'header' => CertificateLayout::normalizeHeader($this->header, $this->template->tokenSet()),
                'elements' => $this->elements,
            ],
        ]);

        Notification::make()
            ->title('Certificate layout saved')
            ->success()
            ->send();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function normalizedElements(): array
    {
        return array_map(function (array $element): array {
            foreach (['x', 'y', 'w', 'h', 'fontSize'] as $numericKey) {
                if (array_key_exists($numericKey, $element)) {
                    $element[$numericKey] = (int) $element[$numericKey];
                }
            }

            if (array_key_exists('visible', $element)) {
                $element['visible'] = filter_var($element['visible'], FILTER_VALIDATE_BOOLEAN);
            }

            return $element;
        }, array_values($this->elements));
    }

    public function resetToDefault(): void
    {
        $this->authorize('update', $this->template);

        $layout = CertificateLayout::default($this->template->tokenSet());
        $this->elements = $layout['elements'];
        $this->signatureCount = $layout['signature_count'];
        $this->border = $layout['border'];
        $this->header = $layout['header'];
        $this->selectedId = null;

        Notification::make()
            ->title('Layout reset to default (save to persist)')
            ->success()
            ->send();
    }

    public function updateSelectedElementBind(?string $bind): void
    {
        if ($this->selectedId === null) {
            return;
        }

        $bind = $bind === '' ? null : $bind;
        $tokenSet = $this->template->tokenSet();

        foreach ($this->elements as $index => $element) {
            if (($element['id'] ?? null) !== $this->selectedId) {
                continue;
            }

            if ($bind !== null && CertificateLayout::isLiveBind($bind, $tokenSet)) {
                $this->elements[$index]['bind'] = $bind;
            } else {
                unset($this->elements[$index]['bind']);
            }

            break;
        }
    }

    /**
     * @return array<string, string>
     */
    public function sampleTokens(): array
    {
        return CertificateLayout::sampleTokens($this->template->tokenSet());
    }

    /**
     * @return array<string, string>
     */
    public function tokenOptions(): array
    {
        $options = [];

        foreach (CertificateLayout::liveBindKeys($this->template->tokenSet()) as $key) {
            $options[$key] = CertificateLayout::tokenLabel($key, $this->template->tokenSet());
        }

        return $options;
    }

    public function render(): View
    {
        return view('filament-certificate-builder::livewire.certificate-layout-designer', [
            'tokens' => $this->sampleTokens(),
            'tokenOptions' => $this->tokenOptions(),
            'tokenSet' => $this->template->tokenSet(),
            'assetUrls' => [
                'logo_1' => $this->template->assetUrl('logo_1'),
                'logo_2' => $this->template->assetUrl('logo_2'),
                'logo_3' => $this->template->assetUrl('logo_3'),
                'signature_1' => $this->template->assetUrl('signature_1'),
                'signature_2' => $this->template->assetUrl('signature_2'),
                'signature_3' => $this->template->assetUrl('signature_3'),
            ],
            'canvasWidth' => CertificateLayout::WIDTH,
            'canvasHeight' => CertificateLayout::HEIGHT,
            'maxSignatureCount' => CertificateLayout::MAX_SIGNATURES,
            'borderStyles' => CertificateLayout::borderStyles($this->border),
            'headerStyles' => CertificateLayout::headerStyles($this->header, $this->template->tokenSet()),
            'headerTitle' => CertificateLayout::resolveHeaderTitle($this->header, $this->sampleTokens(), $this->template->tokenSet()),
            'headerImageUrl' => $this->template->assetUrl('header'),
        ]);
    }
}
