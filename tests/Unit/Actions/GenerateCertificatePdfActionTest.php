<?php

declare(strict_types=1);

use Spatie\Browsershot\Browsershot;
use Tapp\FilamentCertificateBuilder\Actions\GenerateCertificatePdfAction;
use Tapp\FilamentCertificateBuilder\Models\CertificateTemplate;

function certificatePdfActionForTesting(): GenerateCertificatePdfAction
{
    return new class extends GenerateCertificatePdfAction
    {
        public function exposeBrowsershotForHtml(string $html): Browsershot
        {
            return $this->browsershotForHtml($html);
        }
    };
}

it('configures browsershot with no sandbox for linux chrome launches', function () {
    config()->set('certificate-builder.chrome_path', null);
    config()->set('services.browsershot.chrome_path', null);

    $action = certificatePdfActionForTesting();

    $browsershot = $action->exposeBrowsershotForHtml('<html></html>');

    expect($browsershot)->toBeInstanceOf(Browsershot::class);

    $command = $browsershot->createPdfCommand();

    expect($command['options']['args'])->toContain('--no-sandbox')
        ->and($command['options']['landscape'])->toBeTrue()
        ->and($command['options']['format'])->toBe('Letter')
        ->and($command['options']['margin'])->toMatchArray([
            'top' => '0mm',
            'right' => '0mm',
            'bottom' => '0mm',
            'left' => '0mm',
        ])
        ->and($command['options']['printBackground'])->toBeTrue()
        ->and($command['options']['waitUntil'])->toBe('networkidle0');
});

it('renders a Letter-sized full-bleed certificate canvas for PDF html', function () {
    $template = CertificateTemplate::defaultTemplate();

    $html = view('filament-certificate-builder::certificate', [
        'template' => $template,
        'tokens' => [
            'recipient_name' => 'Scott Grayson',
            'course_name' => 'Certificate PDF Integration Course',
            'date_range' => 'Oct 5, 2026',
        ],
    ])->render();

    expect($html)
        ->toContain('size: letter landscape')
        ->toContain('width: 11in')
        ->toContain('height: 8.5in')
        ->toContain('margin: 0')
        ->not->toContain('margin: 10px auto')
        ->toContain('Scott Grayson');
});

it('applies the configured chrome path when set', function () {
    config()->set('certificate-builder.chrome_path', '/usr/bin/chromium');

    $action = certificatePdfActionForTesting();

    $command = $action->exposeBrowsershotForHtml('<html></html>')->createPdfCommand();

    expect($command['options']['executablePath'])->toBe('/usr/bin/chromium');
});

it('generates a pdf via the configured browsershot instance', function () {
    $template = CertificateTemplate::defaultTemplate();
    $tokens = [
        'recipient_name' => 'Jane Doe',
        'course_name' => 'Safety Training',
        'date_range' => 'January 2026',
    ];

    $action = Mockery::mock(GenerateCertificatePdfAction::class)
        ->makePartial()
        ->shouldAllowMockingProtectedMethods();

    $browsershot = Mockery::mock(Browsershot::class);
    $browsershot->shouldReceive('pdf')->once()->andReturn('%PDF-1.4 fake');

    $action->shouldReceive('browsershotForHtml')
        ->once()
        ->withArgs(fn (string $html): bool => str_contains($html, 'Jane Doe')
            && str_contains($html, 'Safety Training'))
        ->andReturn($browsershot);

    expect($action->handle($template, $tokens))->toBe('%PDF-1.4 fake');
});
