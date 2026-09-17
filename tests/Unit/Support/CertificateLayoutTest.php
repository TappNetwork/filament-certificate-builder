<?php

declare(strict_types=1);

use Tapp\FilamentCertificateBuilder\Support\CertificateLayout;

it('uses Letter landscape design dimensions at 96dpi', function () {
    expect(CertificateLayout::WIDTH)->toBe(1056)
        ->and(CertificateLayout::HEIGHT)->toBe(816)
        ->and(CertificateLayout::default()['width'])->toBe(1056)
        ->and(CertificateLayout::default()['height'])->toBe(816);
});

it('migrates legacy canvas size to Letter landscape without moving elements', function () {
    $layout = CertificateLayout::normalize([
        'width' => 1050,
        'height' => 774,
        'elements' => [
            [
                'id' => 'recipient_name',
                'type' => 'text',
                'bind' => 'recipient_name',
                'x' => 50,
                'y' => 245,
                'w' => 950,
                'h' => 45,
                'visible' => true,
            ],
        ],
    ]);

    $element = collect($layout['elements'])->firstWhere('id', 'recipient_name');

    expect($layout['width'])->toBe(CertificateLayout::WIDTH)
        ->and($layout['height'])->toBe(CertificateLayout::HEIGHT)
        ->and($element['x'])->toBe(50)
        ->and($element['y'])->toBe(245)
        ->and($element['w'])->toBe(950);
});

it('treats catalog tokens as live binds for the requested set', function () {
    expect(CertificateLayout::isLiveBind('recipient_name', 'default'))->toBeTrue()
        ->and(CertificateLayout::isLiveBind('course_name', 'default'))->toBeTrue()
        ->and(CertificateLayout::isLiveBind('date_range', 'default'))->toBeTrue()
        ->and(CertificateLayout::isLiveBind('certifying_line', 'default'))->toBeFalse()
        ->and(CertificateLayout::isLiveBind(null, 'default'))->toBeFalse();
});

it('treats a config token as live only on the set that declares it', function () {
    config()->set('certificate-builder.token_sets.default.tokens.hours', [
        'label' => 'Contact hours',
        'sample' => '40',
    ]);

    expect(CertificateLayout::isLiveBind('hours', 'default'))->toBeTrue()
        ->and(CertificateLayout::isLiveBind('hours', 'course'))->toBeFalse()
        ->and(CertificateLayout::sampleTokens('default')['hours'])->toBe('40')
        ->and(CertificateLayout::tokenLabel('hours', 'default'))->toBe('Contact hours');
});

it('resolves live binds from tokens and static text from the element', function () {
    $tokens = [
        'recipient_name' => 'Jane Doe',
        'course_name' => 'CHW',
        'date_range' => 'January 1st - February 1st 2026',
    ];

    expect(CertificateLayout::resolveElementText(['bind' => 'recipient_name'], $tokens, 'default'))
        ->toBe('Jane Doe')
        ->and(CertificateLayout::resolveElementText([
            'text' => 'Custom certifying copy',
        ], $tokens, 'default'))->toBe('Custom certifying copy');
});

it('uses default copy from the requested token set', function () {
    config()->set('certificate-builder.token_sets.course.default_copy', [
        'certifying_line' => 'This course certifies that',
        'completed_line' => 'has finished',
        'description' => 'Course-specific description.',
    ]);

    $default = collect(CertificateLayout::defaultElements('default'))->keyBy('id');
    $course = collect(CertificateLayout::defaultElements('course'))->keyBy('id');

    expect($default['certifying_line']['text'])->toBe('This certifies that')
        ->and($course['certifying_line']['text'])->toBe('This course certifies that')
        ->and($course['completed_line']['text'])->toBe('has finished')
        ->and($course['description']['text'])->toBe('Course-specific description.');
});

it('normalizes leftover binds using the token set default copy', function () {
    config()->set('certificate-builder.token_sets.course.default_copy.certifying_line', 'Course leftover copy');

    $default = CertificateLayout::normalizeElements([
        ['id' => 'certifying_line', 'type' => 'text', 'bind' => 'certifying_line'],
    ], 'default');

    $course = CertificateLayout::normalizeElements([
        ['id' => 'certifying_line', 'type' => 'text', 'bind' => 'certifying_line'],
    ], 'course');

    expect($default[0]['text'])->toBe('This certifies that')
        ->and($course[0]['text'])->toBe('Course leftover copy');
});

it('stores editable default copy on static text elements', function () {
    $elements = collect(CertificateLayout::defaultElements())->keyBy('id');

    expect($elements['certifying_line'])->not->toHaveKey('bind')
        ->and($elements['certifying_line']['text'])->not->toBeEmpty()
        ->and($elements['completed_line'])->not->toHaveKey('bind')
        ->and($elements['description'])->not->toHaveKey('bind')
        ->and($elements['course_name']['bind'])->toBe('course_name')
        ->and($elements['recipient_name']['bind'])->toBe('recipient_name')
        ->and($elements['date_range']['bind'])->toBe('date_range');
});

it('keeps logos separate from signature blocks', function () {
    $elements = collect(CertificateLayout::defaultElements())->keyBy('id');

    expect($elements['logo_1']['type'])->toBe('image')
        ->and($elements['logo_1']['source'])->toBe('logo_1')
        ->and($elements['signature_1']['type'])->toBe('signature')
        ->and($elements['signature_1']['source'])->toBe('signature_1')
        ->and(CertificateLayout::default()['signature_count'])->toBe(2)
        ->and(CertificateLayout::MAX_SIGNATURES)->toBe(3);
});

it('normalizes legacy logo and signature image elements', function () {
    $layout = CertificateLayout::normalize([
        'elements' => [
            [
                'id' => 'logo',
                'type' => 'image',
                'source' => 'logo',
                'x' => 10,
                'y' => 20,
                'w' => 100,
                'h' => 50,
                'visible' => true,
            ],
            [
                'id' => 'signature_left',
                'type' => 'signature',
                'source' => 'signature_left',
                'name' => 'Ada',
                'title' => 'Director',
                'x' => 1,
                'y' => 2,
                'w' => 3,
                'h' => 4,
                'visible' => true,
            ],
        ],
    ]);

    $byId = collect($layout['elements'])->keyBy('id');

    expect($byId['logo_1']['source'])->toBe('logo_1')
        ->and($byId)->toHaveKeys(['logo_1', 'logo_2', 'logo_3', 'signature_1'])
        ->and($byId['signature_1']['source'])->toBe('signature_1')
        ->and($byId['signature_1']['name'])->toBe('Ada')
        ->and($layout['signature_count'])->toBe(1);
});

it('defaults to a double gray border matching the legacy certificate', function () {
    $border = CertificateLayout::default()['border'];

    expect($border['style'])->toBe('double')
        ->and($border['color'])->toBe('#a1a1aa')
        ->and($border['width'])->toBe(8)
        ->and($border['inner_color'])->toBe('#d4d4d8')
        ->and($border['inner_width'])->toBe(4);
});

it('normalizes missing border config to the default double frame', function () {
    $layout = CertificateLayout::normalize([
        'elements' => [],
    ]);

    expect($layout['border'])->toBe(CertificateLayout::defaultBorder())
        ->and(CertificateLayout::borderStyles($layout['border'])['canvas'])->toContain('#a1a1aa')
        ->and(CertificateLayout::borderStyles($layout['border'])['inner'])->toContain('#d4d4d8');
});

it('builds gradient and solid border styles and rejects unsafe values', function () {
    $gradient = CertificateLayout::normalizeBorder([
        'style' => 'gradient',
        'width' => 6,
        'color' => '#a3e635',
        'gradient' => 'linear-gradient(to right, #a3e635, #0ea5e9, #67e8f9)',
    ]);
    $solid = CertificateLayout::normalizeBorder([
        'style' => 'solid',
        'width' => 6,
        'color' => '#B5498F',
    ]);
    $unsafe = CertificateLayout::normalizeBorder([
        'style' => 'gradient',
        'color' => 'red; background: url(evil)',
        'gradient' => 'url(javascript:alert(1))',
    ]);

    expect($gradient['gradient'])->toBe('linear-gradient(to right, #a3e635, #0ea5e9, #67e8f9)')
        ->and(CertificateLayout::borderStyles($gradient)['canvas'])->toContain('linear-gradient(to right, #a3e635, #0ea5e9, #67e8f9)')
        ->and(CertificateLayout::borderStyles($gradient)['inner'])->toContain('inset:6px')
        ->and(CertificateLayout::borderStyles($solid)['canvas'])->toBe('border:6px solid #B5498F;')
        ->and($unsafe['color'])->toBe('#a1a1aa')
        ->and($unsafe['gradient'])->toBe('');
});

it('defaults to a disabled banner header', function () {
    $header = CertificateLayout::default()['header'];

    expect($header['enabled'])->toBeFalse()
        ->and($header['height'])->toBe(220)
        ->and(CertificateLayout::headerStyles($header))->toBe('display:none;');
});

it('normalizes missing header config and rejects unsafe background values', function () {
    $layout = CertificateLayout::normalize([
        'elements' => [],
    ]);
    $header = CertificateLayout::normalizeHeader([
        'enabled' => true,
        'height' => 300,
        'background_color' => '#B5498F',
        'background_size' => '60% auto',
        'background_position' => 'center center',
        'title_bind' => 'course_name',
        'title_color' => '#ffffff',
        'subtitle' => 'CERTIFICATE OF COMPLETION',
    ]);
    $unsafe = CertificateLayout::normalizeHeader([
        'enabled' => true,
        'background_color' => 'red; background: url(evil)',
        'background_size' => 'url(javascript:alert(1))',
        'background_position' => 'center / cover',
    ]);

    expect($layout['header'])->toBe(CertificateLayout::defaultHeader())
        ->and($header['enabled'])->toBeTrue()
        ->and($header['title_bind'])->toBe('course_name')
        ->and(CertificateLayout::headerStyles($header))->toContain('height:300px')
        ->and(CertificateLayout::headerStyles($header))->toContain('#B5498F')
        ->and(CertificateLayout::resolveHeaderTitle($header, ['course_name' => 'DECAN']))->toBe('DECAN')
        ->and($unsafe['background_color'])->toBe('')
        ->and($unsafe['background_size'])->toBe('cover')
        ->and($unsafe['background_position'])->toBe('center');
});

it('syncs signature fields up to three while preserving edits and sources', function () {
    $elements = CertificateLayout::defaultElements();
    $elements = CertificateLayout::syncSignatureElements($elements, 3);

    $signatures = collect($elements)
        ->filter(fn (array $element): bool => $element['type'] === 'signature')
        ->values();

    expect($signatures)->toHaveCount(3)
        ->and($signatures[0]['name'])->toBe('Signer 1')
        ->and($signatures[0]['source'])->toBe('signature_1')
        ->and($signatures[2]['id'])->toBe('signature_3')
        ->and($signatures[2]['source'])->toBe('signature_3');

    $reduced = CertificateLayout::syncSignatureElements($elements, 1);

    expect(collect($reduced)->where('type', 'signature'))->toHaveCount(1)
        ->and(collect($reduced)->firstWhere('type', 'signature')['source'])->toBe('signature_1');
});
