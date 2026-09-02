<?php

declare(strict_types=1);

namespace Tapp\FilamentCertificateBuilder\Actions;

use Tapp\FilamentCertificateBuilder\Contracts\ResolvesCertificateTokens;
use Tapp\FilamentCertificateBuilder\Support\CertificateLayout;

class ResolveSampleCertificateTokensAction implements ResolvesCertificateTokens
{
    /**
     * @param  array<string, mixed>  $context
     * @return array<string, string>
     */
    public function resolve(array $context): array
    {
        $tokenSet = is_string($context['token_set'] ?? null)
            ? $context['token_set']
            : CertificateLayout::defaultTokenSet();

        return CertificateLayout::sampleTokens($tokenSet);
    }
}
