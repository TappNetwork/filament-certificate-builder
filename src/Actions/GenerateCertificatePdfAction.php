<?php

declare(strict_types=1);

namespace Tapp\FilamentCertificateBuilder\Actions;

use Spatie\Browsershot\Browsershot;
use Tapp\FilamentCertificateBuilder\Models\CertificateTemplate;

class GenerateCertificatePdfAction
{
    /**
     * @param  array<string, string>  $tokens
     */
    public function handle(CertificateTemplate $template, array $tokens): string
    {
        $html = view('filament-certificate-builder::certificate', [
            'template' => $template,
            'tokens' => $tokens,
        ])->render();

        return $this->browsershotForHtml($html)->pdf();
    }

    protected function browsershotForHtml(string $html): Browsershot
    {
        $browsershot = Browsershot::html($html)
            ->noSandbox()
            ->waitUntilNetworkIdle()
            ->showBackground()
            ->landscape()
            ->format('Letter')
            ->margins(0, 0, 0, 0);

        $chromePath = config('certificate-builder.chrome_path')
            ?? config('services.browsershot.chrome_path');

        if (is_string($chromePath) && $chromePath !== '') {
            $browsershot->setChromePath($chromePath);
        }

        return $browsershot;
    }
}
