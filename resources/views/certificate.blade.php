@php
    use Tapp\FilamentCertificateBuilder\Models\CertificateTemplate;
    use Tapp\FilamentCertificateBuilder\Support\CertificateLayout;

    /** @var CertificateTemplate $template */
    /** @var array<string, string> $tokens */
    $template ??= CertificateTemplate::defaultTemplate();
    $tokens ??= [];
    $tokenSet = $template->tokenSet();
    $layout = $template->resolvedLayout();
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Laravel') }}</title>
    <style>
        @page {
            size: letter landscape;
            margin: 0;
        }
        html, body {
            margin: 0;
            padding: 0;
            background: #fff;
        }
        .certificate-canvas {
            position: relative;
            /*
             * Letter landscape (11in × 8.5in). Prefer inches so PDF/print drivers
             * fill the page regardless of CSS px DPI assumptions. Constants
             * CertificateLayout::WIDTH/HEIGHT match this at 96dpi (1056×816).
             */
            width: 11in;
            height: 8.5in;
            margin: 0;
            border: 8px solid #a1a1aa;
            box-sizing: border-box;
            font-family: ui-serif, Georgia, Cambria, "Times New Roman", Times, serif;
            background: #fff;
        }
        .certificate-inner {
            position: absolute;
            inset: 10px;
            border: 4px solid #d4d4d8;
        }
    </style>
</head>
<body>
    <div class="certificate-canvas">
        <div class="certificate-inner">
            @foreach ($layout['elements'] as $element)
                @continue(! ($element['visible'] ?? true))

                @php
                    $type = $element['type'] ?? 'text';
                    $text = CertificateLayout::resolveElementText($element, $tokens, $tokenSet);
                    $source = $element['source'] ?? null;
                    $imageUrl = $source ? $template->assetUrl($source) : '';
                @endphp

                <div style="
                    position: absolute;
                    left: {{ (int) ($element['x'] ?? 0) }}px;
                    top: {{ (int) ($element['y'] ?? 0) }}px;
                    width: {{ (int) ($element['w'] ?? 0) }}px;
                    height: {{ (int) ($element['h'] ?? 0) }}px;
                    overflow: hidden;
                ">
                    @if ($type === 'image')
                        @if ($imageUrl !== '')
                            <img src="{{ $imageUrl }}" alt="" style="display:block;width:100%;height:100%;object-fit:contain;" />
                        @endif
                    @elseif ($type === 'signature')
                        <div style="display:flex;height:100%;flex-direction:column;align-items:center;justify-content:flex-end;text-align:center;padding:0 8px;">
                            @if ($imageUrl !== '')
                                <img src="{{ $imageUrl }}" alt="" style="width:100%;height:55%;object-fit:contain;margin-bottom:4px;" />
                            @endif
                            <div style="width:100%;flex-shrink:0;border-top:1px solid #a1a1aa;padding-top:4px;font-size:14px;">
                                <div style="font-weight:600;">{{ $element['name'] ?? '' }}</div>
                                <div style="font-style:italic;color:#52525b;">{{ $element['title'] ?? '' }}</div>
                            </div>
                        </div>
                    @else
                        <div style="
                            display:flex;
                            width:100%;
                            height:100%;
                            align-items:center;
                            justify-content: {{ match($element['align'] ?? 'center') { 'left' => 'flex-start', 'right' => 'flex-end', default => 'center' } }};
                            font-size: {{ (int) ($element['fontSize'] ?? 16) }}px;
                            font-weight: {{ $element['fontWeight'] ?? '400' }};
                            font-style: {{ $element['fontStyle'] ?? 'normal' }};
                            text-align: {{ $element['align'] ?? 'center' }};
                            line-height: 1.2;
                            padding: 0 4px;
                            box-sizing: border-box;
                        ">
                            <span style="width:100%;">{{ $text }}</span>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</body>
</html>
