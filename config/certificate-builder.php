<?php

declare(strict_types=1);

use Tapp\FilamentCertificateBuilder\Actions\ResolveSampleCertificateTokensAction;
use Tapp\FilamentCertificateBuilder\Filament\Resources\CertificateTemplates\CertificateTemplateResource;

return [
    'default_token_set' => 'default',

    'token_sets' => [
        'default' => [
            'label' => 'Default',
            'resolver' => ResolveSampleCertificateTokensAction::class,
            'tokens' => [
                'recipient_name' => [
                    'label' => 'Recipient name',
                    'sample' => 'Jane Doe',
                ],
                'course_name' => [
                    'label' => 'Course name',
                    'sample' => 'Sample Course',
                ],
                'date_range' => [
                    'label' => 'Date range',
                    'sample' => 'January 15th - March 15th 2026',
                ],
            ],
            'default_copy' => [
                'certifying_line' => 'This certifies that',
                'completed_line' => 'has successfully completed',
                'description' => '',
            ],
        ],
    ],

    'default_signers' => [
        1 => ['name' => 'Signer 1', 'title' => 'Title'],
        2 => ['name' => 'Signer 2', 'title' => 'Title'],
        3 => ['name' => 'Signer 3', 'title' => 'Title'],
    ],

    'fallback_assets' => [],

    'chrome_path' => null,

    'navigation_group' => 'Certificates',

    'navigation_sort' => 55,

    'preview_template_route' => null,

    'resources' => [
        CertificateTemplateResource::class,
    ],
];
