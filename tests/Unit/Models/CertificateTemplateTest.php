<?php

declare(strict_types=1);

use Mockery\MockInterface;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Tapp\FilamentCertificateBuilder\Models\CertificateTemplate;
use Tapp\FilamentCertificateBuilder\Support\CertificateLayout;

/**
 * @param  array<string, mixed>  $attributes
 * @return Media&MockInterface
 */
function mockCertificateMedia(array $attributes = []): Media
{
    /** @var Media&MockInterface $media */
    $media = Mockery::mock(Media::class)->makePartial();
    $media->setRawAttributes(array_merge([
        'id' => 1,
        'collection_name' => 'logo_1',
        'name' => 'logo',
        'file_name' => 'logo.png',
        'disk' => 'public',
        'size' => 100,
        'manipulations' => [],
        'custom_properties' => [],
        'generated_conversions' => [],
        'responsive_images' => [],
    ], $attributes));

    return $media;
}

it('returns the default layout when layout is empty', function () {
    $template = new CertificateTemplate(['name' => 'Blank']);

    expect($template->resolvedLayout())->toBe(CertificateLayout::default());
});

it('falls back to configured certificate images when media is missing', function () {
    config()->set('certificate-builder.fallback_assets', [
        'logo_1' => 'images/certificate-logo.png',
        'signature_1' => 'images/president-signature.png',
        'signature_2' => 'images/coordinator-signature.png',
    ]);

    $template = CertificateTemplate::defaultTemplate();

    expect($template->assetUrl('logo_1'))->toContain('certificate-logo.png')
        ->and($template->assetUrl('signature_1'))->toContain('president-signature.png')
        ->and($template->assetUrl('signature_2'))->toContain('coordinator-signature.png')
        ->and($template->assetUrl('logo_2'))->toBe('')
        ->and($template->assetUrl('signature_3'))->toBe('');
});

it('returns an empty string when media and fallback assets are missing', function () {
    config()->set('certificate-builder.fallback_assets', []);

    $template = CertificateTemplate::defaultTemplate();

    expect($template->assetUrl('logo_1'))->toBe('')
        ->and($template->assetUrl('signature_1'))->toBe('');
});

it('uses a temporary url when the disk supports signed urls', function () {
    config()->set([
        'certificate-builder.media.use_signed_urls' => true,
        'certificate-builder.media.signed_url_expiration' => 60,
        'filesystems.disks.s3' => [
            'driver' => 's3',
            'key' => 'test-key',
            'secret' => 'test-secret',
            'region' => 'us-east-1',
            'bucket' => 'test-bucket',
        ],
    ]);

    $signedUrl = 'https://test-bucket.s3.amazonaws.com/1304/logo.png?X-Amz-Signature=abc123';

    $media = mockCertificateMedia(['disk' => 's3']);
    $media->shouldReceive('getTemporaryUrl')
        ->once()
        ->andReturn($signedUrl);
    $media->shouldReceive('getUrl')->never();

    /** @var CertificateTemplate&MockInterface $template */
    $template = Mockery::mock(CertificateTemplate::class)->makePartial();
    $template->shouldReceive('getFirstMedia')
        ->with('logo_1')
        ->once()
        ->andReturn($media);

    expect($template->assetUrl('logo_1'))->toBe($signedUrl);
});

it('falls back to getUrl when signed urls are disabled', function () {
    config()->set([
        'certificate-builder.media.use_signed_urls' => false,
        'filesystems.disks.s3' => [
            'driver' => 's3',
            'key' => 'test-key',
            'secret' => 'test-secret',
            'region' => 'us-east-1',
            'bucket' => 'test-bucket',
        ],
    ]);

    $publicUrl = 'https://test-bucket.s3.amazonaws.com/1304/logo.png';

    $media = mockCertificateMedia(['disk' => 's3']);
    $media->shouldReceive('getTemporaryUrl')->never();
    $media->shouldReceive('getUrl')
        ->once()
        ->andReturn($publicUrl);

    /** @var CertificateTemplate&MockInterface $template */
    $template = Mockery::mock(CertificateTemplate::class)->makePartial();
    $template->shouldReceive('getFirstMedia')
        ->with('logo_1')
        ->once()
        ->andReturn($media);

    expect($template->assetUrl('logo_1'))->toBe($publicUrl);
});

it('falls back to getUrl when temporary url generation fails', function () {
    config()->set([
        'certificate-builder.media.use_signed_urls' => true,
        'filesystems.disks.s3' => [
            'driver' => 's3',
            'key' => 'test-key',
            'secret' => 'test-secret',
            'region' => 'us-east-1',
            'bucket' => 'test-bucket',
        ],
    ]);

    $publicUrl = 'https://test-bucket.s3.amazonaws.com/1304/logo.png';

    $media = mockCertificateMedia(['disk' => 's3']);
    $media->shouldReceive('getTemporaryUrl')
        ->once()
        ->andThrow(new RuntimeException('Unable to create temporary URL'));
    $media->shouldReceive('getUrl')
        ->once()
        ->andReturn($publicUrl);

    /** @var CertificateTemplate&MockInterface $template */
    $template = Mockery::mock(CertificateTemplate::class)->makePartial();
    $template->shouldReceive('getFirstMedia')
        ->with('logo_1')
        ->once()
        ->andReturn($media);

    expect($template->assetUrl('logo_1'))->toBe($publicUrl);
});

it('uses getUrl for local disks that do not support temporary urls', function () {
    config()->set([
        'certificate-builder.media.use_signed_urls' => true,
        'app.url' => 'http://localhost',
        'filesystems.disks.public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => '/storage',
        ],
    ]);

    $localUrl = 'http://localhost/storage/1/logo.png';

    $media = mockCertificateMedia(['disk' => 'public']);
    $media->shouldReceive('getTemporaryUrl')->never();
    $media->shouldReceive('getUrl')
        ->once()
        ->andReturn($localUrl);

    /** @var CertificateTemplate&MockInterface $template */
    $template = Mockery::mock(CertificateTemplate::class)->makePartial();
    $template->shouldReceive('getFirstMedia')
        ->with('logo_1')
        ->once()
        ->andReturn($media);

    expect($template->assetUrl('logo_1'))->toBe('/storage/1/logo.png');
});

it('resolves legacy media collections through signed urls', function () {
    config()->set([
        'certificate-builder.media.use_signed_urls' => true,
        'certificate-builder.fallback_assets' => [],
        'filesystems.disks.s3' => [
            'driver' => 's3',
            'key' => 'test-key',
            'secret' => 'test-secret',
            'region' => 'us-east-1',
            'bucket' => 'test-bucket',
        ],
    ]);

    $signedUrl = 'https://test-bucket.s3.amazonaws.com/legacy/logo.png?X-Amz-Signature=legacy';

    $media = mockCertificateMedia([
        'disk' => 's3',
        'collection_name' => 'image_1',
    ]);
    $media->shouldReceive('getTemporaryUrl')
        ->once()
        ->andReturn($signedUrl);

    /** @var CertificateTemplate&MockInterface $template */
    $template = Mockery::mock(CertificateTemplate::class)->makePartial();
    $template->shouldReceive('getFirstMedia')
        ->with('logo_1')
        ->once()
        ->andReturn(null);
    $template->shouldReceive('getFirstMedia')
        ->with('image_1')
        ->once()
        ->andReturn($media);

    expect($template->assetUrl('logo_1'))->toBe($signedUrl);
});

it('localizes same-host public asset URLs and leaves remote URLs absolute', function () {
    config()->set('app.url', 'http://mch-lms-api.test');

    expect(CertificateTemplate::localizePublicUrl('http://mch-lms-api.test/storage/2/DPH_logo_family.png'))
        ->toBe('/storage/2/DPH_logo_family.png')
        ->and(CertificateTemplate::localizePublicUrl('http://mch-lms-api.test/img/DPH_logo_family.png?v=1'))
        ->toBe('/img/DPH_logo_family.png?v=1')
        ->and(CertificateTemplate::localizePublicUrl('https://s3.amazonaws.com/bucket/DPH_logo_family.png'))
        ->toBe('https://s3.amazonaws.com/bucket/DPH_logo_family.png');
});

it('renders configured canvas border styles', function () {
    $template = CertificateTemplate::factory()->create([
        'layout' => [
            ...CertificateLayout::default(),
            'border' => [
                'style' => 'gradient',
                'width' => 6,
                'color' => '#a3e635',
                'gradient' => 'linear-gradient(to right, #a3e635, #0ea5e9, #67e8f9)',
            ],
        ],
    ]);

    $html = view('filament-certificate-builder::certificate', [
        'template' => $template,
        'tokens' => CertificateLayout::sampleTokens($template->tokenSet()),
    ])->render();

    expect($html)
        ->toContain('linear-gradient(to right, #a3e635, #0ea5e9, #67e8f9)')
        ->toContain('inset:6px')
        ->toContain('print-color-adjust: exact')
        ->toContain('-webkit-print-color-adjust: exact');
});

it('renders a configured banner header', function () {
    $template = CertificateTemplate::factory()->create([
        'layout' => [
            ...CertificateLayout::default(),
            'header' => [
                'enabled' => true,
                'height' => 220,
                'background_color' => '#B5498F',
                'title_bind' => 'course_name',
                'subtitle' => 'CERTIFICATE OF COMPLETION',
                'title_transform' => 'uppercase',
            ],
        ],
    ]);

    $html = view('filament-certificate-builder::certificate', [
        'template' => $template,
        'tokens' => ['course_name' => 'DECAN Skills'],
    ])->render();

    expect($html)
        ->toContain('certificate-header')
        ->toContain('DECAN Skills')
        ->toContain('CERTIFICATE OF COMPLETION')
        ->toContain('height:220px')
        ->toContain('background-color:#B5498F')
        ->toContain('print-color-adjust: exact');
});

it('persists a custom layout', function () {
    $template = CertificateTemplate::factory()->create();

    $layout = CertificateLayout::default();
    $layout['elements'][0]['x'] = 12;

    $template->update(['layout' => $layout]);

    expect($template->fresh()->resolvedLayout()['elements'][0]['x'])->toBe(12);
});
