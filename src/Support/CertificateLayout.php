<?php

declare(strict_types=1);

namespace Tapp\FilamentCertificateBuilder\Support;

use InvalidArgumentException;

class CertificateLayout
{
    public const WIDTH = 1050;

    public const HEIGHT = 774;

    private const MAX_LOGOS = 3;

    public const MAX_SIGNATURES = 3;

    public const DEFAULT_SIGNATURE_COUNT = 2;

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
     * @return array{width: int, height: int, signature_count: int, elements: list<array<string, mixed>>}
     */
    public static function default(?string $tokenSet = null): array
    {
        return [
            'width' => self::WIDTH,
            'height' => self::HEIGHT,
            'signature_count' => self::DEFAULT_SIGNATURE_COUNT,
            'elements' => self::defaultElements($tokenSet),
        ];
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
     * Hidden elements and text boxes whose resolved copy is empty are not drawn.
     *
     * @param  array<string, mixed>  $element
     * @param  array<string, string>  $tokens
     */
    public static function shouldRenderElement(array $element, array $tokens, ?string $tokenSet = null): bool
    {
        if (! ($element['visible'] ?? true)) {
            return false;
        }

        if (($element['type'] ?? 'text') !== 'text') {
            return true;
        }

        return trim(self::resolveElementText($element, $tokens, $tokenSet)) !== '';
    }

    /**
     * @param  array{width?: int, height?: int, signature_count?: int, elements?: list<array<string, mixed>>}  $layout
     * @return array{width: int, height: int, signature_count: int, elements: list<array<string, mixed>>}
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

        return [
            'width' => (int) ($layout['width'] ?? self::WIDTH),
            'height' => (int) ($layout['height'] ?? self::HEIGHT),
            'signature_count' => $signatureCount,
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
}
