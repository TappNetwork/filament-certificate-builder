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

Optionally publish the config:

```bash
php artisan vendor:publish --tag="filament-certificate-builder-config"
```

## Token sets

Each issuing model (training, course, etc.) gets its own token set in `config/certificate-builder.php`. The set lists live field keys, sample values for the designer, default static copy, and a resolver class that implements `ResolvesCertificateTokens`.

```php
'token_sets' => [
    'training' => [
        'label' => 'Training',
        'resolver' => App\Certificates\TrainingCertificateTokenResolver::class,
        'tokens' => [
            'recipient_name' => ['label' => 'Recipient name', 'sample' => 'Jane Doe'],
            'course_name' => ['label' => 'Course name', 'sample' => 'Community Health Worker'],
            'date_range' => ['label' => 'Date range', 'sample' => 'January 15th - March 15th 2026'],
        ],
        'default_copy' => [
            'certifying_line' => 'This certifies that',
            'completed_line' => 'has successfully completed',
            'description' => '',
        ],
    ],
],
```

Render a certificate with a template and resolved tokens:

```php
use Tapp\FilamentCertificateBuilder\Actions\GenerateCertificatePdfAction;

$html = view('filament-certificate-builder::certificate', [
    'template' => $template,
    'tokens' => $tokens,
])->render();

$pdf = app(GenerateCertificatePdfAction::class)->handle($template, $tokens);
```

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
