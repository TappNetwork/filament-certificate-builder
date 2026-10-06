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
        $this->addMediaCollection('header')->singleFile();
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
     * @return array{width: int, height: int, signature_count: int, border: array{style: string, color: string, width: int, inner_color: string, inner_width: int, inner_inset: int, gradient: string}, header: array{enabled: bool, height: int, background_color: string, background_size: string, background_position: string, title_bind: string, title: string, title_color: string, title_size: int, title_transform: string, subtitle: string, subtitle_color: string, subtitle_size: int}, elements: list<array<string, mixed>>}
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
            return self::localizePublicUrl($media->getUrl());
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
                return self::localizePublicUrl($legacyMedia->getUrl());
            }
        }

        $fallbacks = config('certificate-builder.fallback_assets', []);
        $path = is_array($fallbacks) ? ($fallbacks[$source] ?? null) : null;

        return is_string($path) && $path !== '' ? self::localizePublicUrl(asset($path)) : '';
    }

    /**
     * Convert same-host public URLs to root-relative paths so certificates
     * still load assets when APP_URL does not match the request host.
     */
    public static function localizePublicUrl(string $url): string
    {
        $parts = parse_url($url);

        if (! is_array($parts)) {
            return $url;
        }

        $host = $parts['host'] ?? null;
        $appHost = parse_url((string) config('app.url'), PHP_URL_HOST);

        if (! is_string($host) || $host === '' || $host !== $appHost) {
            return $url;
        }

        $path = $parts['path'] ?? null;

        if (! is_string($path) || $path === '') {
            return $url;
        }

        $query = $parts['query'] ?? null;
        $suffix = is_string($query) && $query !== '' ? '?' . $query : '';

        return $path . $suffix;
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
