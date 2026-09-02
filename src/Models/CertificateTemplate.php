<?php

declare(strict_types=1);

namespace Tapp\FilamentCertificateBuilder\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Tapp\FilamentCertificateBuilder\Database\Factories\CertificateTemplateFactory;
use Tapp\FilamentCertificateBuilder\Support\CertificateLayout;

/**
 * @property string $name
 * @property string|null $token_set
 * @property array<string, mixed>|null $layout
 */
class CertificateTemplate extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'layout' => 'array',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logo_1')->singleFile();
        $this->addMediaCollection('logo_2')->singleFile();
        $this->addMediaCollection('logo_3')->singleFile();
        $this->addMediaCollection('signature_1')->singleFile();
        $this->addMediaCollection('signature_2')->singleFile();
        $this->addMediaCollection('signature_3')->singleFile();

        $this->addMediaCollection('image_1')->singleFile();
        $this->addMediaCollection('image_2')->singleFile();
        $this->addMediaCollection('image_3')->singleFile();
        $this->addMediaCollection('logo')->singleFile();
        $this->addMediaCollection('signature_left')->singleFile();
        $this->addMediaCollection('signature_right')->singleFile();
    }

    /**
     * @return array{width: int, height: int, signature_count: int, elements: list<array<string, mixed>>}
     */
    public function resolvedLayout(): array
    {
        $layout = $this->layout;

        if (! is_array($layout) || ! isset($layout['elements']) || ! is_array($layout['elements'])) {
            return CertificateLayout::default($this->tokenSet());
        }

        return CertificateLayout::normalize($layout, $this->tokenSet());
    }

    public function tokenSet(): string
    {
        if (is_string($this->token_set) && $this->token_set !== '') {
            return $this->token_set;
        }

        return CertificateLayout::defaultTokenSet();
    }

    public function assetUrl(string $source): string
    {
        $media = $this->getFirstMedia($source);

        if ($media !== null) {
            return $media->getUrl();
        }

        $legacySources = match ($source) {
            'logo_1' => ['image_1', 'logo'],
            'logo_2' => ['image_2'],
            'logo_3' => ['image_3'],
            'signature_1' => ['signature_left'],
            'signature_2' => ['signature_right'],
            default => [],
        };

        foreach ($legacySources as $legacySource) {
            $legacyMedia = $this->getFirstMedia($legacySource);

            if ($legacyMedia !== null) {
                return $legacyMedia->getUrl();
            }
        }

        $fallbacks = config('certificate-builder.fallback_assets', []);
        $path = is_array($fallbacks) ? ($fallbacks[$source] ?? null) : null;

        return is_string($path) && $path !== '' ? asset($path) : '';
    }

    public static function defaultTemplate(): self
    {
        return new self([
            'name' => 'Default Certificate',
            'token_set' => CertificateLayout::defaultTokenSet(),
            'layout' => CertificateLayout::default(),
        ]);
    }

    protected static function newFactory(): Factory
    {
        return CertificateTemplateFactory::new();
    }
}
