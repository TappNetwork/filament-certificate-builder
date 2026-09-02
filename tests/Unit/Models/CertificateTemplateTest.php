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

it('persists a custom layout', function () {
    $template = CertificateTemplate::factory()->create();

    $layout = CertificateLayout::default();
    $layout['elements'][0]['x'] = 12;

    $template->update(['layout' => $layout]);

    expect($template->fresh()->resolvedLayout()['elements'][0]['x'])->toBe(12);
});
