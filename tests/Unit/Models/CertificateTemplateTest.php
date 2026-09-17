<?php

declare(strict_types=1);

use Tapp\FilamentCertificateBuilder\Models\CertificateTemplate;
use Tapp\FilamentCertificateBuilder\Support\CertificateLayout;

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
