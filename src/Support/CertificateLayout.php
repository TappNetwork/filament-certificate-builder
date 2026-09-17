<?php

declare(strict_types=1);

namespace Tapp\FilamentCertificateBuilder\Support;

use InvalidArgumentException;

class CertificateLayout
{
    /** Letter landscape width at 96dpi (11in). */
    public const WIDTH = 1056;

    /** Letter landscape height at 96dpi (8.5in). */
    public const HEIGHT = 816;

    /** @deprecated Legacy design size before Letter landscape canvas. */
    private const LEGACY_WIDTH = 1050;

    /** @deprecated Legacy design size before Letter landscape canvas. */
    private const LEGACY_HEIGHT = 774;

    private const MAX_LOGOS = 3;

    public const MAX_SIGNATURES = 3;

    public const DEFAULT_SIGNATURE_COUNT = 2;

    public const BORDER_STYLES = ['none', 'solid', 'double', 'gradient'];

    public const DEFAULT_BORDER_COLOR = '#a1a1aa';

    public const DEFAULT_INNER_BORDER_COLOR = '#d4d4d8';

    public const DEFAULT_BORDER_WIDTH = 8;

    public const DEFAULT_INNER_BORDER_WIDTH = 4;

    public const DEFAULT_INNER_INSET = 10;

    public static function defaultTokenSet(): string
    {
        return (string) config('certificate-builder.default_token_set', 'default');
    }

    /**
     * @return array<string, string>
     */
    public static function tokenSetOptions(): array
    {
        $sets = config('certificate-builder.token_sets', []);

        if (! is_array($sets)) {
            return [];
        }

        $options = [];

        foreach ($sets as $key => $definition) {
            $options[(string) $key] = is_array($definition)
                ? (string) ($definition['label'] ?? $key)
                : (string) $key;
        }

        return $options;
    }

    /**
     * @return list<string>
     */
    public static function liveBindKeys(?string $tokenSet = null): array
    {
        $tokens = self::tokenDefinitions($tokenSet);

        return array_keys($tokens);
    }

    public static function isLiveBind(?string $bind, ?string $tokenSet = null): bool
    {
        return $bind !== null && in_array($bind, self::liveBindKeys($tokenSet), true);
    }

    /**
     * @return array<string, string>
     */
    public static function sampleTokens(?string $tokenSet = null): array
    {
        $samples = [];

        foreach (self::tokenDefinitions($tokenSet) as $key => $definition) {
            $samples[$key] = (string) ($definition['sample'] ?? '');
        }

        return $samples;
    }

    public static function tokenLabel(string $key, ?string $tokenSet = null): string
    {
        $label = self::tokenDefinitions($tokenSet)[$key]['label'] ?? null;

        if (is_string($label) && $label !== '') {
            return $label;
        }

        return str_replace('_', ' ', $key);
    }

    public static function resolverClass(?string $tokenSet = null): string
    {
        $tokenSet ??= self::defaultTokenSet();
        $class = config('certificate-builder.token_sets.' . $tokenSet . '.resolver');

        if (! is_string($class) || $class === '') {
            throw new InvalidArgumentException('No certificate token resolver configured for set [' . $tokenSet . '].');
        }

        return $class;
    }

    /**
     * Default certificate layout matching the legacy Blade certificate.
     *
     * @return array{width: int, height: int, signature_count: int, border: array{style: string, color: string, width: int, inner_color: string, inner_width: int, inner_inset: int, gradient: string}, header: array{enabled: bool, height: int, background_color: string, background_size: string, background_position: string, title_bind: string, title: string, title_color: string, title_size: int, title_transform: string, subtitle: string, subtitle_color: string, subtitle_size: int}, elements: list<array<string, mixed>>}
     */
    public static function default(?string $tokenSet = null): array
    {
        return [
            'width' => self::WIDTH,
            'height' => self::HEIGHT,
            'signature_count' => self::DEFAULT_SIGNATURE_COUNT,
            'border' => self::defaultBorder(),
            'header' => self::defaultHeader(),
            'elements' => self::defaultElements($tokenSet),
        ];
    }

    /**
     * @return array{style: string, color: string, width: int, inner_color: string, inner_width: int, inner_inset: int, gradient: string}
     */
    public static function defaultBorder(): array
    {
        return [
            'style' => 'double',
            'color' => self::DEFAULT_BORDER_COLOR,
            'width' => self::DEFAULT_BORDER_WIDTH,
            'inner_color' => self::DEFAULT_INNER_BORDER_COLOR,
            'inner_width' => self::DEFAULT_INNER_BORDER_WIDTH,
            'inner_inset' => self::DEFAULT_INNER_INSET,
            'gradient' => '',
        ];
    }

    /**
     * @param  array<string, mixed>  $border
     * @return array{style: string, color: string, width: int, inner_color: string, inner_width: int, inner_inset: int, gradient: string}
     */
    public static function normalizeBorder(array $border): array
    {
        $default = self::defaultBorder();
        $style = isset($border['style']) && is_string($border['style']) ? $border['style'] : $default['style'];

        if (! in_array($style, self::BORDER_STYLES, true)) {
            $style = $default['style'];
        }

        return [
            'style' => $style,
            'color' => self::sanitizeHexColor($border['color'] ?? null, $default['color']),
            'width' => max(0, min(40, (int) ($border['width'] ?? $default['width']))),
            'inner_color' => self::sanitizeHexColor($border['inner_color'] ?? null, $default['inner_color']),
            'inner_width' => max(0, min(20, (int) ($border['inner_width'] ?? $default['inner_width']))),
            'inner_inset' => max(0, min(40, (int) ($border['inner_inset'] ?? $default['inner_inset']))),
            'gradient' => self::sanitizeGradient(
                isset($border['gradient']) && is_string($border['gradient']) ? $border['gradient'] : ''
            ),
        ];
    }

    /**
     * @param  array<string, mixed>  $border
     * @return array{canvas: string, inner: string}
     */
    public static function borderStyles(array $border): array
    {
        $border = self::normalizeBorder($border);

        return match ($border['style']) {
            'none' => [
                'canvas' => 'border:none;',
                'inner' => 'inset:0;border:none;',
            ],
            'solid' => [
                'canvas' => 'border:' . $border['width'] . 'px solid ' . $border['color'] . ';',
                'inner' => 'inset:0;border:none;',
            ],
            'gradient' => [
                'canvas' => 'border:none;background:' . ($border['gradient'] !== '' ? $border['gradient'] : $border['color']) . ';',
                'inner' => 'inset:' . $border['width'] . 'px;border:none;background:#fff;',
            ],
            default => [
                'canvas' => 'border:' . $border['width'] . 'px solid ' . $border['color'] . ';',
                'inner' => 'inset:' . $border['inner_inset'] . 'px;border:' . $border['inner_width'] . 'px solid ' . $border['inner_color'] . ';',
            ],
        };
    }

    /**
     * @return array{enabled: bool, height: int, background_color: string, background_size: string, background_position: string, title_bind: string, title: string, title_color: string, title_size: int, title_transform: string, subtitle: string, subtitle_color: string, subtitle_size: int}
     */
    public static function defaultHeader(): array
    {
        return [
            'enabled' => false,
            'height' => 220,
            'background_color' => '',
            'background_size' => 'cover',
            'background_position' => 'center',
            'title_bind' => '',
            'title' => '',
            'title_color' => '#ffffff',
            'title_size' => 36,
            'title_transform' => 'none',
            'subtitle' => '',
            'subtitle_color' => '#111827',
            'subtitle_size' => 28,
        ];
    }

    /**
     * @param  array<string, mixed>  $header
     * @return array{enabled: bool, height: int, background_color: string, background_size: string, background_position: string, title_bind: string, title: string, title_color: string, title_size: int, title_transform: string, subtitle: string, subtitle_color: string, subtitle_size: int}
     */
    public static function normalizeHeader(array $header, ?string $tokenSet = null): array
    {
        $default = self::defaultHeader();
        $titleBind = isset($header['title_bind']) && is_string($header['title_bind'])
            ? $header['title_bind']
            : $default['title_bind'];
        $transform = isset($header['title_transform']) && is_string($header['title_transform'])
            ? $header['title_transform']
            : $default['title_transform'];

        if (! in_array($transform, ['none', 'uppercase'], true)) {
            $transform = $default['title_transform'];
        }

        return [
            'enabled' => filter_var($header['enabled'] ?? $default['enabled'], FILTER_VALIDATE_BOOLEAN),
            'height' => max(40, min(400, (int) ($header['height'] ?? $default['height']))),
            'background_color' => self::sanitizeOptionalHexColor($header['background_color'] ?? null),
            'background_size' => self::sanitizeBackgroundSize(
                isset($header['background_size']) && is_string($header['background_size'])
                    ? $header['background_size']
                    : $default['background_size']
            ),
            'background_position' => self::sanitizeBackgroundPosition(
                isset($header['background_position']) && is_string($header['background_position'])
                    ? $header['background_position']
                    : $default['background_position']
            ),
            'title_bind' => self::isLiveBind($titleBind, $tokenSet) ? $titleBind : '',
            'title' => isset($header['title']) && is_string($header['title']) ? $header['title'] : $default['title'],
            'title_color' => self::sanitizeHexColor($header['title_color'] ?? null, $default['title_color']),
            'title_size' => max(12, min(72, (int) ($header['title_size'] ?? $default['title_size']))),
            'title_transform' => $transform,
            'subtitle' => isset($header['subtitle']) && is_string($header['subtitle']) ? $header['subtitle'] : $default['subtitle'],
            'subtitle_color' => self::sanitizeHexColor($header['subtitle_color'] ?? null, $default['subtitle_color']),
            'subtitle_size' => max(12, min(72, (int) ($header['subtitle_size'] ?? $default['subtitle_size']))),
        ];
    }

    /**
     * @param  array<string, mixed>  $header
     */
    public static function headerStyles(array $header, ?string $tokenSet = null): string
    {
        $header = self::normalizeHeader($header, $tokenSet);

        if (! $header['enabled']) {
            return 'display:none;';
        }

        $styles = 'display:flex;flex-direction:column;align-items:center;justify-content:center;box-sizing:border-box;width:100%;height:'
            . $header['height']
            . 'px;padding:24px 32px;text-align:center;background-repeat:no-repeat;background-size:'
            . $header['background_size']
            . ';background-position:'
            . $header['background_position']
            . ';';

        if ($header['background_color'] !== '') {
            $styles .= 'background-color:' . $header['background_color'] . ';';
        }

        return $styles;
    }

    /**
     * @param  array<string, mixed>  $header
     * @param  array<string, string>  $tokens
     */
    public static function resolveHeaderTitle(array $header, array $tokens, ?string $tokenSet = null): string
    {
        $header = self::normalizeHeader($header, $tokenSet);
        $bind = $header['title_bind'];

        if ($bind !== '' && self::isLiveBind($bind, $tokenSet)) {
            return (string) ($tokens[$bind] ?? '');
        }

        return $header['title'];
    }

    /**
     * @param  array<string, mixed>  $element
     * @param  array<string, string>  $tokens
     */
    public static function resolveElementText(array $element, array $tokens, ?string $tokenSet = null): string
    {
        $bind = isset($element['bind']) && is_string($element['bind'])
            ? $element['bind']
            : null;

        if (self::isLiveBind($bind, $tokenSet)) {
            return (string) ($tokens[$bind] ?? '');
        }

        return (string) ($element['text'] ?? '');
    }

    /**
     * @param  array{width?: int, height?: int, signature_count?: int, border?: array<string, mixed>, header?: array<string, mixed>, elements?: list<array<string, mixed>>}  $layout
     * @return array{width: int, height: int, signature_count: int, border: array{style: string, color: string, width: int, inner_color: string, inner_width: int, inner_inset: int, gradient: string}, header: array{enabled: bool, height: int, background_color: string, background_size: string, background_position: string, title_bind: string, title: string, title_color: string, title_size: int, title_transform: string, subtitle: string, subtitle_color: string, subtitle_size: int}, elements: list<array<string, mixed>>}
     */
    public static function normalize(array $layout, ?string $tokenSet = null): array
    {
        $elements = self::normalizeElements(
            is_array($layout['elements'] ?? null) ? $layout['elements'] : [],
            $tokenSet,
        );

        $signatureCount = (int) ($layout['signature_count'] ?? self::countSignatures($elements));
        $signatureCount = max(0, min(self::MAX_SIGNATURES, $signatureCount));

        $elements = self::ensureLogoSlots($elements);
        $elements = self::syncSignatureElements($elements, $signatureCount);

        $width = (int) ($layout['width'] ?? self::WIDTH);
        $height = (int) ($layout['height'] ?? self::HEIGHT);

        // Migrate the pre-Letter design size so PDF/HTML canvas fills the page
        // without shifting existing absolute element coordinates.
        if ($width === self::LEGACY_WIDTH && $height === self::LEGACY_HEIGHT) {
            $width = self::WIDTH;
            $height = self::HEIGHT;
        }

        return [
            'width' => $width,
            'height' => $height,
            'signature_count' => $signatureCount,
            'border' => self::normalizeBorder(is_array($layout['border'] ?? null) ? $layout['border'] : []),
            'header' => self::normalizeHeader(is_array($layout['header'] ?? null) ? $layout['header'] : [], $tokenSet),
            'elements' => $elements,
        ];
    }

    /**
     * Normalize persisted layouts: migrate legacy binds/images/signatures.
     *
     * @param  array<int, array<string, mixed>>  $elements
     * @return list<array<string, mixed>>
     */
    public static function normalizeElements(array $elements, ?string $tokenSet = null): array
    {
        $legacyStaticText = self::defaultCopy($tokenSet);

        $legacyLogoMap = [
            'logo' => 1,
            'image_1' => 1,
            'image_2' => 2,
            'image_3' => 3,
        ];

        $legacySignatureMap = [
            'signature_left' => 1,
            'signature_right' => 2,
        ];

        foreach ($elements as $index => $element) {
            $id = isset($element['id']) && is_string($element['id']) ? $element['id'] : null;
            $type = $element['type'] ?? null;
            $source = isset($element['source']) && is_string($element['source']) ? $element['source'] : null;
            $bind = isset($element['bind']) && is_string($element['bind'])
                ? $element['bind']
                : null;

            if ($id !== null && array_key_exists($id, $legacyLogoMap)) {
                $number = $legacyLogoMap[$id];
                $elements[$index]['id'] = 'logo_' . $number;
                $elements[$index]['type'] = 'image';
                $elements[$index]['source'] = 'logo_' . $number;
                $elements[$index]['label'] = $element['label'] ?? 'Logo ' . $number;
            } elseif ($type === 'image' && $source !== null && array_key_exists($source, $legacyLogoMap)) {
                $number = $legacyLogoMap[$source];
                $elements[$index]['id'] = 'logo_' . $number;
                $elements[$index]['source'] = 'logo_' . $number;
                $elements[$index]['label'] = $element['label'] ?? 'Logo ' . $number;
            }

            if ($id !== null && array_key_exists($id, $legacySignatureMap)) {
                $number = $legacySignatureMap[$id];
                $elements[$index]['id'] = 'signature_' . $number;
                $elements[$index]['type'] = 'signature';
                $elements[$index]['source'] = 'signature_' . $number;
                $elements[$index]['label'] = 'Signature ' . $number;
            }

            if (($elements[$index]['type'] ?? null) === 'signature') {
                $signatureId = $elements[$index]['id'] ?? null;

                if (is_string($signatureId) && preg_match('/^signature_([1-3])$/', $signatureId, $matches) === 1) {
                    $elements[$index]['source'] = 'signature_' . $matches[1];
                    $elements[$index]['label'] = 'Signature ' . $matches[1];
                }
            }

            if ($bind === 'program_name') {
                $elements[$index]['bind'] = 'course_name';
                $elements[$index]['label'] = $element['label'] ?? 'Course Name';

                continue;
            }

            if ($bind === null || self::isLiveBind($bind, $tokenSet)) {
                continue;
            }

            if (! array_key_exists('text', $element) || $element['text'] === null || $element['text'] === '') {
                $elements[$index]['text'] = $legacyStaticText[$bind] ?? '';
            }

            unset($elements[$index]['bind']);
        }

        return array_values($elements);
    }

    /**
     * @param  list<array<string, mixed>>  $elements
     * @return list<array<string, mixed>>
     */
    public static function ensureLogoSlots(array $elements): array
    {
        $byId = collect($elements)->keyBy(fn (array $element): string => (string) ($element['id'] ?? uniqid('el_')));

        for ($i = 1; $i <= self::MAX_LOGOS; $i++) {
            $id = 'logo_' . $i;

            if ($byId->has($id)) {
                $existing = $byId->get($id);
                $existing['type'] = 'image';
                $existing['source'] = $id;
                $existing['label'] = $existing['label'] ?? 'Logo ' . $i;
                $byId->put($id, $existing);

                continue;
            }

            $byId->put($id, self::defaultLogoElement($i));
        }

        return array_values($byId->all());
    }

    /**
     * @param  list<array<string, mixed>>  $elements
     * @return list<array<string, mixed>>
     */
    public static function syncSignatureElements(array $elements, int $count): array
    {
        $count = max(0, min(self::MAX_SIGNATURES, $count));

        $nonSignatures = array_values(array_filter(
            $elements,
            fn (array $element): bool => ($element['type'] ?? null) !== 'signature'
        ));

        $existingSignatures = collect($elements)
            ->filter(fn (array $element): bool => ($element['type'] ?? null) === 'signature')
            ->values();

        $signatures = [];

        for ($i = 1; $i <= $count; $i++) {
            $existing = $existingSignatures->first(
                fn (array $element): bool => ($element['id'] ?? null) === 'signature_' . $i
            );

            if ($existing === null) {
                $existing = $existingSignatures->get($i - 1);
            }

            $signatures[] = self::makeSignatureElement($i, is_array($existing) ? $existing : null);
        }

        return [...$nonSignatures, ...$signatures];
    }

    /**
     * @param  array<string, mixed>|null  $existing
     * @return array<string, mixed>
     */
    public static function makeSignatureElement(int $number, ?array $existing = null): array
    {
        $defaults = self::defaultSignatureElement($number);

        if ($existing === null) {
            return $defaults;
        }

        return [
            ...$defaults,
            'x' => (int) ($existing['x'] ?? $defaults['x']),
            'y' => (int) ($existing['y'] ?? $defaults['y']),
            'w' => (int) ($existing['w'] ?? $defaults['w']),
            'h' => (int) ($existing['h'] ?? $defaults['h']),
            'name' => (string) ($existing['name'] ?? $defaults['name']),
            'title' => (string) ($existing['title'] ?? $defaults['title']),
            'visible' => (bool) ($existing['visible'] ?? true),
            'source' => 'signature_' . $number,
            'label' => 'Signature ' . $number,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $elements
     */
    public static function countSignatures(array $elements): int
    {
        return collect($elements)
            ->filter(fn (array $element): bool => ($element['type'] ?? null) === 'signature')
            ->count();
    }

    /**
     * @return array<string, mixed>
     */
    public static function defaultLogoElement(int $number): array
    {
        return match ($number) {
            1 => [
                'id' => 'logo_1',
                'type' => 'image',
                'source' => 'logo_1',
                'label' => 'Logo 1',
                'x' => 375,
                'y' => 30,
                'w' => 300,
                'h' => 90,
                'visible' => true,
            ],
            2 => [
                'id' => 'logo_2',
                'type' => 'image',
                'source' => 'logo_2',
                'label' => 'Logo 2',
                'x' => 40,
                'y' => 30,
                'w' => 160,
                'h' => 80,
                'visible' => false,
            ],
            default => [
                'id' => 'logo_' . $number,
                'type' => 'image',
                'source' => 'logo_' . $number,
                'label' => 'Logo ' . $number,
                'x' => 850,
                'y' => 30,
                'w' => 160,
                'h' => 80,
                'visible' => false,
            ],
        };
    }

    /**
     * @return array<string, mixed>
     */
    public static function defaultSignatureElement(int $number): array
    {
        $positions = [
            1 => ['x' => 120, 'y' => 540],
            2 => ['x' => 610, 'y' => 540],
            3 => ['x' => 365, 'y' => 540],
        ];

        $signers = self::defaultSigners();

        $position = $positions[$number] ?? ['x' => 120, 'y' => 540];
        $identity = $signers[$number] ?? ['name' => 'Signer ' . $number, 'title' => 'Title'];

        return [
            'id' => 'signature_' . $number,
            'type' => 'signature',
            'source' => 'signature_' . $number,
            'label' => 'Signature ' . $number,
            'x' => $position['x'],
            'y' => $position['y'],
            'w' => 320,
            'h' => 140,
            'name' => $identity['name'],
            'title' => $identity['title'],
            'visible' => true,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function defaultElements(?string $tokenSet = null): array
    {
        $copy = self::defaultCopy($tokenSet);

        return [
            self::defaultLogoElement(1),
            self::defaultLogoElement(2),
            self::defaultLogoElement(3),
            [
                'id' => 'recipient_name_display',
                'type' => 'text',
                'bind' => 'recipient_name',
                'label' => 'Recipient Name (header)',
                'x' => 50,
                'y' => 130,
                'w' => 950,
                'h' => 55,
                'fontSize' => 48,
                'fontWeight' => '800',
                'fontStyle' => 'italic',
                'align' => 'center',
                'visible' => true,
            ],
            [
                'id' => 'certifying_line',
                'type' => 'text',
                'text' => $copy['certifying_line'] ?? '',
                'label' => 'Certifying Line',
                'x' => 50,
                'y' => 200,
                'w' => 950,
                'h' => 36,
                'fontSize' => 22,
                'fontWeight' => '400',
                'fontStyle' => 'normal',
                'align' => 'center',
                'visible' => true,
            ],
            [
                'id' => 'recipient_name',
                'type' => 'text',
                'bind' => 'recipient_name',
                'label' => 'Recipient Name',
                'x' => 50,
                'y' => 245,
                'w' => 950,
                'h' => 45,
                'fontSize' => 32,
                'fontWeight' => '800',
                'fontStyle' => 'normal',
                'align' => 'center',
                'visible' => true,
            ],
            [
                'id' => 'completed_line',
                'type' => 'text',
                'text' => $copy['completed_line'] ?? '',
                'label' => 'Completed Line',
                'x' => 50,
                'y' => 300,
                'w' => 950,
                'h' => 30,
                'fontSize' => 22,
                'fontWeight' => '400',
                'fontStyle' => 'normal',
                'align' => 'center',
                'visible' => true,
            ],
            [
                'id' => 'course_name',
                'type' => 'text',
                'bind' => 'course_name',
                'label' => 'Course Name',
                'x' => 50,
                'y' => 340,
                'w' => 950,
                'h' => 40,
                'fontSize' => 24,
                'fontWeight' => '800',
                'fontStyle' => 'normal',
                'align' => 'center',
                'visible' => true,
            ],
            [
                'id' => 'description',
                'type' => 'text',
                'text' => $copy['description'] ?? '',
                'label' => 'Description',
                'x' => 80,
                'y' => 390,
                'w' => 890,
                'h' => 80,
                'fontSize' => 18,
                'fontWeight' => '400',
                'fontStyle' => 'normal',
                'align' => 'center',
                'visible' => true,
            ],
            [
                'id' => 'date_range',
                'type' => 'text',
                'bind' => 'date_range',
                'label' => 'Date Range',
                'x' => 50,
                'y' => 480,
                'w' => 950,
                'h' => 36,
                'fontSize' => 22,
                'fontWeight' => '700',
                'fontStyle' => 'normal',
                'align' => 'center',
                'visible' => true,
            ],
            self::defaultSignatureElement(1),
            self::defaultSignatureElement(2),
        ];
    }

    /**
     * @return array<string, array{label?: string, sample?: string}>
     */
    private static function tokenDefinitions(?string $tokenSet = null): array
    {
        $tokenSet ??= self::defaultTokenSet();
        $tokens = config('certificate-builder.token_sets.' . $tokenSet . '.tokens', []);

        if (! is_array($tokens)) {
            return [];
        }

        $definitions = [];

        foreach ($tokens as $key => $definition) {
            $definitions[(string) $key] = is_array($definition) ? $definition : [];
        }

        return $definitions;
    }

    /**
     * @return array<string, string>
     */
    private static function defaultCopy(?string $tokenSet = null): array
    {
        $tokenSet ??= self::defaultTokenSet();
        $copy = config('certificate-builder.token_sets.' . $tokenSet . '.default_copy', []);

        if (! is_array($copy)) {
            return [];
        }

        $normalized = [];

        foreach ($copy as $key => $value) {
            $normalized[(string) $key] = (string) $value;
        }

        return $normalized;
    }

    /**
     * @return array<int, array{name: string, title: string}>
     */
    private static function defaultSigners(): array
    {
        $signers = config('certificate-builder.default_signers', []);

        if (! is_array($signers)) {
            return [];
        }

        $normalized = [];

        foreach ($signers as $number => $signer) {
            if (! is_array($signer)) {
                continue;
            }

            $normalized[(int) $number] = [
                'name' => (string) ($signer['name'] ?? 'Signer ' . $number),
                'title' => (string) ($signer['title'] ?? 'Title'),
            ];
        }

        return $normalized;
    }

    private static function sanitizeHexColor(mixed $color, string $fallback): string
    {
        if (! is_string($color)) {
            return $fallback;
        }

        $color = trim($color);

        if (preg_match('/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $color) === 1) {
            return $color;
        }

        return $fallback;
    }

    private static function sanitizeOptionalHexColor(mixed $color): string
    {
        if (! is_string($color) || trim($color) === '') {
            return '';
        }

        return self::sanitizeHexColor($color, '');
    }

    private static function sanitizeBackgroundSize(string $size): string
    {
        $size = trim($size);

        if (in_array($size, ['cover', 'contain', 'auto'], true)) {
            return $size;
        }

        if (preg_match('/^\d{1,3}%(?:\s+(?:auto|\d{1,3}%))?$/', $size) === 1) {
            return $size;
        }

        return 'cover';
    }

    private static function sanitizeBackgroundPosition(string $position): string
    {
        $position = trim($position);

        if (preg_match('/^(?:center|top|bottom|left|right)(?:\s+(?:center|top|bottom|left|right))?$/', $position) === 1) {
            return $position;
        }

        return 'center';
    }

    private static function sanitizeGradient(string $gradient): string
    {
        $gradient = trim($gradient);

        if ($gradient === '') {
            return '';
        }

        if (preg_match('/^linear-gradient\((?:to (?:right|left|top|bottom)|[0-9]{1,3}deg),\s*(?:#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})(?:,\s*)?){2,4}\)$/', $gradient) === 1) {
            return $gradient;
        }

        return '';
    }
}
