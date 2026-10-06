# Filament Certificate Builder

[![Latest Version on Packagist](https://img.shields.io/packagist/v/tapp/filament-certificate-builder.svg?style=flat-square)](https://packagist.org/packages/tapp/filament-certificate-builder)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/TappNetwork/filament-certificate-builder/run-tests.yml?branch=5.x&label=tests&style=flat-square)](https://github.com/TappNetwork/filament-certificate-builder/actions?query=workflow%3Arun-tests+branch%3A5.x)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/TappNetwork/filament-certificate-builder/fix-php-code-style-issues.yml?branch=5.x&label=code%20style&style=flat-square)](https://github.com/TappNetwork/filament-certificate-builder/actions?query=workflow%3A"Fix+PHP+code+styling"+branch%3A5.x)
[![Total Downloads](https://img.shields.io/packagist/dt/tapp/filament-certificate-builder.svg?style=flat-square)](https://packagist.org/packages/tapp/filament-certificate-builder)

A Filament plugin for designing certificate templates and rendering them as HTML or PDF. Host apps register token sets and a resolver; the package never imports your domain models.

## Installation

```bash
composer require tapp/filament-certificate-builder
```

Register the plugin on your Filament panel:

```php
use Tapp\FilamentCertificateBuilder\FilamentCertificateBuilderPlugin;

$panel->plugin(FilamentCertificateBuilderPlugin::make());
```

If you have not set up a custom theme, follow the [Filament theme docs](https://filamentphp.com/docs/4.x/styling/overview#creating-a-custom-theme) first, then add:

```css
@source '../../../../vendor/tapp/filament-certificate-builder/resources/**/*.blade.php';
```

Publish and run the migrations:

```bash
php artisan vendor:publish --tag="filament-certificate-builder-migrations"
php artisan migrate
```

If `certificate_templates` already exists in your app, skip the package create-table migration. Add a `token_set` column yourself if it is missing.

Optionally publish the config:

```bash
php artisan vendor:publish --tag="filament-certificate-builder-config"
```

## Configuration

Published config lives in `config/certificate-builder.php`. The package ships a single `default` token set. Override that file in the host app with your own token sets, copy, signers, and assets.

```php
use App\Certificates\CourseCertificateTokenResolver;
use Tapp\FilamentCertificateBuilder\Filament\Resources\CertificateTemplates\CertificateTemplateResource;

return [
    'default_token_set' => 'course',

    'token_sets' => [
        'course' => [
            'label' => 'Course',
            'resolver' => CourseCertificateTokenResolver::class,
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

    'fallback_assets' => [
        'logo_1' => 'images/certificate-logo.png',
        'signature_1' => 'images/signer-one.png',
        'signature_2' => 'images/signer-two.png',
    ],

    'chrome_path' => env('BROWSERSHOT_CHROME_PATH'),

    'navigation_group' => 'Certificates',

    'navigation_sort' => 55,

    'preview_template_route' => null,

    'resources' => [
        CertificateTemplateResource::class,
    ],
];
```

| Key | Purpose |
| --- | --- |
| `default_token_set` | Token set used for new templates and `CertificateTemplate::defaultTemplate()`. |
| `token_sets` | Named issuing contexts (course, training, etc.). Each has a label, resolver, live tokens, and default static copy. |
| `default_signers` | Name and title shown on new/reset layouts for signature slots 1–3. |
| `fallback_assets` | Public asset paths used when a template has no uploaded media for that slot. Keys match media collections (`logo_1`, `signature_1`, …). |
| `chrome_path` | Chrome/Chromium binary for Browsershot PDF generation. Falls back to `services.browsershot.chrome_path`. |
| `navigation_group` / `navigation_sort` | Filament nav placement for the package resource. |
| `preview_template_route` | Named route for “Preview with sample data” on the edit page. The route receives the template model. Set to `null` to hide the button. |
| `resources` | Filament resources registered by the plugin. Set to `[]` if the host app provides its own `CertificateTemplate` resource. |

The token-set select on the template form is shown only when `token_sets` has more than one entry.

### Token sets

Each issuing model gets its own token set. The set lists:

- **`label`** — shown in the token-set select
- **`resolver`** — class that implements `Tapp\FilamentCertificateBuilder\Contracts\ResolvesCertificateTokens`
- **`tokens`** — live field keys bound in the designer. `sample` values appear in the designer and preview; `label` is the designer field name
- **`default_copy`** — static text seeded into new/reset layouts (`certifying_line`, `completed_line`, `description`, plus any extra keys you add)

Existing saved layouts are not rewritten when you change `default_copy`. That copy is used for new templates and Reset to default.

The package never imports your domain models. Resolvers live in the host app and receive a context array you pass at render time.

### Resolver class

Implement `ResolvesCertificateTokens` and return a `string` value for every key in that token set:

```php
<?php

declare(strict_types=1);

namespace App\Certificates;

use App\Models\Course;
use App\Models\User;
use Tapp\FilamentCertificateBuilder\Contracts\ResolvesCertificateTokens;

class CourseCertificateTokenResolver implements ResolvesCertificateTokens
{
    /**
     * @param  array<string, mixed>  $context
     * @return array<string, string>
     */
    public function resolve(array $context): array
    {
        $course = $context['course'] ?? null;
        $user = $context['user'] ?? null;

        if (! $course instanceof Course || ! $user instanceof User) {
            return [
                'recipient_name' => '',
                'course_name' => '',
                'date_range' => '',
            ];
        }

        $recipientName = trim(($user->first_name ?? '').' '.($user->last_name ?? ''));

        if ($recipientName === '') {
            $recipientName = (string) ($user->name ?? '');
        }

        return [
            'recipient_name' => $recipientName,
            'course_name' => (string) $course->name,
            'date_range' => (string) $course->completed_at?->toFormattedDateString(),
        ];
    }
}
```

Register that class on the matching token set:

```php
'token_sets' => [
    'course' => [
        'label' => 'Course',
        'resolver' => App\Certificates\CourseCertificateTokenResolver::class,
        // ...
    ],
],
```

Resolve tokens at issue time, then render HTML or PDF:

```php
use Tapp\FilamentCertificateBuilder\Actions\GenerateCertificatePdfAction;
use Tapp\FilamentCertificateBuilder\Contracts\ResolvesCertificateTokens;
use Tapp\FilamentCertificateBuilder\Support\CertificateLayout;

$resolver = app(CertificateLayout::resolverClass($template->tokenSet()));

if (! $resolver instanceof ResolvesCertificateTokens) {
    $tokens = CertificateLayout::sampleTokens($template->tokenSet());
} else {
    $tokens = $resolver->resolve([
        'course' => $course,
        'user' => $user,
    ]);
}

$html = view('filament-certificate-builder::certificate', [
    'template' => $template,
    'tokens' => $tokens,
])->render();

$pdf = app(GenerateCertificatePdfAction::class)->handle($template, $tokens);
```

### Filament LMS course certificates

[tapp/filament-lms](https://github.com/TappNetwork/filament-lms) can optionally assign a template per course. LMS does **not** add a package-default `course` token set — hosts register that set themselves. LMS creates templates from **Edit Course** (name + token set + default layout) and keeps `filament-lms::certificates.show` / `filament-lms::certificates.download` for issue and download. The builder create page does not need a `?course=` hook.

When rendering a course certificate, LMS calls your resolver with:

```php
$resolver->resolve([
    'course' => $course, // Tapp\FilamentLms\Models\Course
    'user' => $user,
]);
```

Add a matching `course` token set in the host `config/certificate-builder.php`. Hosts that already have `certificate_templates` skip the package create-table migration (see Installation).

`CertificateLayout::sampleTokens($tokenSet)` returns the configured sample values. Use that for designer previews when you do not have a live course/user.

The built-in `ResolveSampleCertificateTokensAction` does exactly that. Point a token set at it if you only need designer samples and have not written a live resolver yet.

### Using your own Filament resource

To keep templates in a host resource (custom nav, extra columns, create-from-elsewhere actions), disable the package resource:

```php
'resources' => [],
```

Your resource can still use the package designer. On the edit page, embed:

```php
use Filament\Schemas\Components\Livewire;
use Tapp\FilamentCertificateBuilder\Livewire\CertificateLayoutDesigner;

Livewire::make(
    CertificateLayoutDesigner::class,
    fn (): array => ['template' => $this->getRecord()],
)->key('certificate-layout-designer-'.$this->getRecord()->getKey());
```

You may extend `Tapp\FilamentCertificateBuilder\Models\CertificateTemplate` in the host app and point your resource at that model.

### Preview route

Set `preview_template_route` to a named route that accepts the template and returns the package certificate view with sample tokens:

```php
use Tapp\FilamentCertificateBuilder\Models\CertificateTemplate;
use Tapp\FilamentCertificateBuilder\Support\CertificateLayout;

Route::get('/certificates/templates/{certificateTemplate}/preview', function (CertificateTemplate $certificateTemplate) {
    return view('filament-certificate-builder::certificate', [
        'template' => $certificateTemplate,
        'tokens' => CertificateLayout::sampleTokens($certificateTemplate->tokenSet()),
    ]);
})->name('certificates.preview-template');
```

Then set `'preview_template_route' => 'certificates.preview-template'`.

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](.github/SECURITY.md) on how to report security vulnerabilities.

## Credits

- [John Wesely](https://github.com/johnwesely)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
