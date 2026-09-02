<?php

declare(strict_types=1);

namespace Tapp\FilamentCertificateBuilder\Contracts;

interface ResolvesCertificateTokens
{
    /**
     * @param  array<string, mixed>  $context
     * @return array<string, string>
     */
    public function resolve(array $context): array;
}
