<?php

declare(strict_types=1);

namespace Tapp\FilamentCertificateBuilder\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Tapp\FilamentCertificateBuilder\Models\CertificateTemplate;
use Tapp\FilamentCertificateBuilder\Support\CertificateLayout;

/**
 * @extends Factory<CertificateTemplate>
 */
class CertificateTemplateFactory extends Factory
{
    protected $model = CertificateTemplate::class;

    /**
     * @return array{name: string, token_set: string, layout: array<string, mixed>}
     */
    public function definition(): array
    {
        $tokenSet = CertificateLayout::defaultTokenSet();

        return [
            'name' => fake()->unique()->words(3, true) . ' Certificate',
            'token_set' => $tokenSet,
            'layout' => CertificateLayout::default($tokenSet),
        ];
    }
}
