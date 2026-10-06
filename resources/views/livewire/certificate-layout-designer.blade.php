<div class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h3 class="text-base font-semibold text-gray-950 dark:text-white">Layout designer</h3>
            <p class="text-sm text-gray-500">Drag to move. Select an element and drag a corner handle to resize. Signature blocks include the image plus the line, name, and title.</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
                <span class="whitespace-nowrap">Signature fields</span>
                <select
                    class="rounded border-gray-300 text-sm dark:border-white/10 dark:bg-gray-800"
                    wire:change="setSignatureCount($event.target.value)"
                >
                    @for ($count = 0; $count <= $maxSignatureCount; $count++)
                        <option value="{{ $count }}" @selected($signatureCount === $count)>{{ $count }}</option>
                    @endfor
                </select>
            </label>
            <x-filament::button color="gray" wire:click="resetToDefault" wire:confirm="Reset layout to the default certificate arrangement? Unsaved changes will be lost.">
                Reset to default
            </x-filament::button>
            <x-filament::button wire:click="save">
                Save layout
            </x-filament::button>
        </div>
    </div>

    <div class="grid gap-4 xl:grid-cols-[1fr_280px]">
        <div
            class="overflow-auto rounded-xl border border-gray-200 bg-zinc-100 p-4 dark:border-white/10 dark:bg-gray-900"
            wire:ignore.self
            x-data="certificateDesigner({
                scale: 0.62,
                width: {{ $canvasWidth }},
                height: {{ $canvasHeight }},
                onMove(id, position) {
                    $wire.updateElementPosition(id, position)
                }
            })"
        >
            <div
                class="relative mx-auto origin-top-left bg-white font-serif shadow-lg"
                style="box-sizing:border-box;{{ $borderStyles['canvas'] }}"
                :style="`width:${width}px;height:${height}px;box-sizing:border-box;transform:scale(${scale});{{ $borderStyles['canvas'] }}`"
            >
                <div
                    class="absolute"
                    style="{{ $borderStyles['inner'] }}"
                    @mousedown.self="$wire.selectElement(null)"
                >
                    @if (($header['enabled'] ?? false))
                        <div
                            class="pointer-events-none absolute top-0 left-0 z-0"
                            style="{{ $headerStyles }}@if ($headerImageUrl !== '') background-image:url('{{ $headerImageUrl }}');@endif"
                        >
                            @if ($headerTitle !== '')
                                <div style="width:100%;font-weight:800;line-height:1.15;color:{{ $header['title_color'] ?? '#ffffff' }};font-size:{{ (int) ($header['title_size'] ?? 36) }}px;text-transform:{{ ($header['title_transform'] ?? 'none') === 'uppercase' ? 'uppercase' : 'none' }};">
                                    {{ $headerTitle }}
                                </div>
                            @endif
                            @if (($header['subtitle'] ?? '') !== '')
                                <div style="width:100%;margin-top:12px;font-weight:800;line-height:1.15;color:{{ $header['subtitle_color'] ?? '#111827' }};font-size:{{ (int) ($header['subtitle_size'] ?? 28) }}px;">
                                    {{ $header['subtitle'] }}
                                </div>
                            @endif
                        </div>
                    @endif
                    @foreach ($elements as $element)
                        @php
                            $isSelected = $selectedId === ($element['id'] ?? null);
                            $visible = (bool) ($element['visible'] ?? true);
                            $type = $element['type'] ?? 'text';
                            $text = \Tapp\FilamentCertificateBuilder\Support\CertificateLayout::resolveElementText($element, $tokens, $tokenSet);
                            $source = $element['source'] ?? null;
                            $imageUrl = $source ? ($assetUrls[$source] ?? '') : '';
                        @endphp

                        <div
                            wire:key="element-{{ $element['id'] }}"
                            class="absolute z-10 cursor-move select-none {{ $isSelected ? 'ring-2 ring-primary-500 ring-offset-1' : 'hover:ring-1 hover:ring-primary-300' }} {{ $visible ? '' : 'opacity-40' }}"
                            style="left: {{ (int) $element['x'] }}px; top: {{ (int) $element['y'] }}px; width: {{ (int) $element['w'] }}px; height: {{ (int) $element['h'] }}px;"
                            data-id="{{ $element['id'] }}"
                            @mousedown.prevent="startDrag($event, '{{ $element['id'] }}'); $wire.selectElement('{{ $element['id'] }}')"
                        >
                            @if ($type === 'image')
                                @if ($imageUrl)
                                    <img src="{{ $imageUrl }}" alt="{{ $element['label'] ?? '' }}" class="h-full w-full object-contain" draggable="false" />
                                @else
                                    <div class="flex h-full items-center justify-center bg-zinc-100 text-xs text-zinc-500">{{ $element['label'] ?? 'Image' }}</div>
                                @endif
                            @elseif ($type === 'signature')
                                <div class="flex h-full flex-col items-center justify-end px-2 text-center">
                                    @if ($imageUrl)
                                        <img src="{{ $imageUrl }}" alt="" class="mb-1 h-[55%] w-full object-contain" draggable="false" />
                                    @else
                                        <div class="mb-1 flex h-[55%] w-full items-center justify-center bg-zinc-100 text-xs text-zinc-500">Signature image</div>
                                    @endif
                                    <div class="w-full shrink-0 border-t border-zinc-400 pt-1 text-sm">
                                        <div class="font-semibold">{{ $element['name'] ?? '' }}</div>
                                        <div class="italic text-zinc-600">{{ $element['title'] ?? '' }}</div>
                                    </div>
                                </div>
                            @else
                                <div
                                    class="flex h-full w-full items-center overflow-hidden px-1"
                                    style="
                                        font-size: {{ (int) ($element['fontSize'] ?? 16) }}px;
                                        font-weight: {{ $element['fontWeight'] ?? '400' }};
                                        font-style: {{ $element['fontStyle'] ?? 'normal' }};
                                        text-align: {{ $element['align'] ?? 'center' }};
                                        justify-content: {{ match($element['align'] ?? 'center') { 'left' => 'flex-start', 'right' => 'flex-end', default => 'center' } }};
                                    "
                                >
                                    <span class="w-full leading-tight">{{ $text }}</span>
                                </div>
                            @endif

                            @if ($isSelected)
                                @foreach ([
                                    'nw' => 'left-0 top-0 -translate-x-1/2 -translate-y-1/2 cursor-nwse-resize',
                                    'ne' => 'right-0 top-0 translate-x-1/2 -translate-y-1/2 cursor-nesw-resize',
                                    'sw' => 'left-0 bottom-0 -translate-x-1/2 translate-y-1/2 cursor-nesw-resize',
                                    'se' => 'right-0 bottom-0 translate-x-1/2 translate-y-1/2 cursor-nwse-resize',
                                ] as $corner => $cornerClasses)
                                    <button
                                        type="button"
                                        aria-label="Resize {{ $corner }}"
                                        class="absolute z-10 h-3 w-3 rounded-full border-2 border-primary-500 bg-white shadow {{ $cornerClasses }}"
                                        @mousedown.stop.prevent="startResize($event, '{{ $element['id'] }}', '{{ $corner }}')"
                                    ></button>
                                @endforeach
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-gray-900">
            <h4 class="mb-3 text-sm font-semibold text-gray-950 dark:text-white">Canvas border</h4>
            <div class="mb-6 space-y-3 text-sm">
                <label class="block space-y-1">
                    <span class="text-xs text-gray-500">Style</span>
                    <select class="w-full rounded border-gray-300 text-sm dark:border-white/10 dark:bg-gray-800" wire:model.live="border.style">
                        @foreach (['none' => 'None', 'solid' => 'Solid', 'double' => 'Double', 'gradient' => 'Gradient'] as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                @if (($border['style'] ?? 'double') !== 'none')
                    <div class="grid grid-cols-2 gap-2">
                        <label class="space-y-1">
                            <span class="text-xs text-gray-500">Width</span>
                            <input type="number" min="0" max="40" class="w-full rounded border-gray-300 text-sm dark:border-white/10 dark:bg-gray-800" wire:model.live="border.width" />
                        </label>
                        <label class="space-y-1">
                            <span class="text-xs text-gray-500">Color</span>
                            <input type="color" class="h-9 w-full rounded border-gray-300 dark:border-white/10 dark:bg-gray-800" wire:model.live="border.color" />
                        </label>
                    </div>
                @endif
                @if (($border['style'] ?? null) === 'double')
                    <div class="grid grid-cols-2 gap-2">
                        <label class="space-y-1">
                            <span class="text-xs text-gray-500">Inner width</span>
                            <input type="number" min="0" max="20" class="w-full rounded border-gray-300 text-sm dark:border-white/10 dark:bg-gray-800" wire:model.live="border.inner_width" />
                        </label>
                        <label class="space-y-1">
                            <span class="text-xs text-gray-500">Inner color</span>
                            <input type="color" class="h-9 w-full rounded border-gray-300 dark:border-white/10 dark:bg-gray-800" wire:model.live="border.inner_color" />
                        </label>
                    </div>
                @endif
                @if (($border['style'] ?? null) === 'gradient')
                    <label class="block space-y-1">
                        <span class="text-xs text-gray-500">Gradient</span>
                        <input
                            type="text"
                            class="w-full rounded border-gray-300 text-sm dark:border-white/10 dark:bg-gray-800"
                            placeholder="linear-gradient(to right, #a3e635, #0ea5e9, #67e8f9)"
                            wire:model.live.debounce.200ms="border.gradient"
                        />
                    </label>
                @endif
            </div>

            <h4 class="mb-3 text-sm font-semibold text-gray-950 dark:text-white">Banner header</h4>
            <div class="mb-6 space-y-3 text-sm">
                <label class="flex items-center gap-2">
                    <input type="checkbox" wire:model.live="header.enabled" />
                    Show banner
                </label>
                @if ($header['enabled'] ?? false)
                    <label class="block space-y-1">
                        <span class="text-xs text-gray-500">Height</span>
                        <input type="number" min="40" max="400" class="w-full rounded border-gray-300 text-sm dark:border-white/10 dark:bg-gray-800" wire:model.live="header.height" />
                    </label>
                    <div class="grid grid-cols-2 gap-2">
                        <label class="space-y-1">
                            <span class="text-xs text-gray-500">Background color</span>
                            <input type="text" placeholder="#B5498F" class="w-full rounded border-gray-300 text-sm dark:border-white/10 dark:bg-gray-800" wire:model.live.debounce.200ms="header.background_color" />
                        </label>
                        <label class="space-y-1">
                            <span class="text-xs text-gray-500">Image size</span>
                            <select class="w-full rounded border-gray-300 text-sm dark:border-white/10 dark:bg-gray-800" wire:model.live="header.background_size">
                                <option value="cover">Cover</option>
                                <option value="contain">Contain</option>
                                <option value="60% auto">60% auto</option>
                                <option value="auto">Auto</option>
                            </select>
                        </label>
                    </div>
                    <label class="block space-y-1">
                        <span class="text-xs text-gray-500">Title source</span>
                        <select class="w-full rounded border-gray-300 text-sm dark:border-white/10 dark:bg-gray-800" wire:model.live="header.title_bind">
                            <option value="">Static text</option>
                            @foreach ($tokenOptions as $tokenKey => $tokenLabel)
                                <option value="{{ $tokenKey }}">{{ $tokenLabel }}</option>
                            @endforeach
                        </select>
                    </label>
                    @if (($header['title_bind'] ?? '') === '')
                        <label class="block space-y-1">
                            <span class="text-xs text-gray-500">Title</span>
                            <input type="text" class="w-full rounded border-gray-300 text-sm dark:border-white/10 dark:bg-gray-800" wire:model.live.debounce.200ms="header.title" />
                        </label>
                    @endif
                    <div class="grid grid-cols-2 gap-2">
                        <label class="space-y-1">
                            <span class="text-xs text-gray-500">Title color</span>
                            <input type="color" class="h-9 w-full rounded border-gray-300 dark:border-white/10 dark:bg-gray-800" wire:model.live="header.title_color" />
                        </label>
                        <label class="space-y-1">
                            <span class="text-xs text-gray-500">Title size</span>
                            <input type="number" min="12" max="72" class="w-full rounded border-gray-300 text-sm dark:border-white/10 dark:bg-gray-800" wire:model.live="header.title_size" />
                        </label>
                    </div>
                    <label class="block space-y-1">
                        <span class="text-xs text-gray-500">Title transform</span>
                        <select class="w-full rounded border-gray-300 text-sm dark:border-white/10 dark:bg-gray-800" wire:model.live="header.title_transform">
                            <option value="none">None</option>
                            <option value="uppercase">Uppercase</option>
                        </select>
                    </label>
                    <label class="block space-y-1">
                        <span class="text-xs text-gray-500">Subtitle</span>
                        <input type="text" class="w-full rounded border-gray-300 text-sm dark:border-white/10 dark:bg-gray-800" wire:model.live.debounce.200ms="header.subtitle" />
                    </label>
                    <div class="grid grid-cols-2 gap-2">
                        <label class="space-y-1">
                            <span class="text-xs text-gray-500">Subtitle color</span>
                            <input type="color" class="h-9 w-full rounded border-gray-300 dark:border-white/10 dark:bg-gray-800" wire:model.live="header.subtitle_color" />
                        </label>
                        <label class="space-y-1">
                            <span class="text-xs text-gray-500">Subtitle size</span>
                            <input type="number" min="12" max="72" class="w-full rounded border-gray-300 text-sm dark:border-white/10 dark:bg-gray-800" wire:model.live="header.subtitle_size" />
                        </label>
                    </div>
                    <p class="text-xs text-gray-500">Upload the banner image on the template form.</p>
                @endif
            </div>

            <h4 class="mb-3 text-sm font-semibold text-gray-950 dark:text-white">Element properties</h4>

            @if ($selectedId)
                @php
                    $selectedIndex = collect($elements)->search(
                        fn (array $element): bool => ($element['id'] ?? null) === $selectedId
                    );
                    $selected = $selectedIndex === false ? null : $elements[$selectedIndex];
                @endphp

                @if ($selected !== null && $selectedIndex !== false)
                    <div class="space-y-3 text-sm" wire:key="selected-properties-{{ $selectedId }}">
                        <div>
                            <div class="font-medium text-gray-700 dark:text-gray-200">{{ $selected['label'] ?? $selectedId }}</div>
                            <div class="text-xs text-gray-500">{{ $selected['type'] ?? 'text' }} · {{ $selectedId }}</div>
                        </div>

                        <label class="flex items-center gap-2">
                            <input type="checkbox" wire:model.live="elements.{{ $selectedIndex }}.visible" />
                            Visible
                        </label>

                        <div class="grid grid-cols-2 gap-2">
                            <label class="space-y-1">
                                <span class="text-xs text-gray-500">X</span>
                                <input type="number" class="w-full rounded border-gray-300 text-sm dark:border-white/10 dark:bg-gray-800" wire:model.live.debounce.200ms="elements.{{ $selectedIndex }}.x" />
                            </label>
                            <label class="space-y-1">
                                <span class="text-xs text-gray-500">Y</span>
                                <input type="number" class="w-full rounded border-gray-300 text-sm dark:border-white/10 dark:bg-gray-800" wire:model.live.debounce.200ms="elements.{{ $selectedIndex }}.y" />
                            </label>
                            <label class="space-y-1">
                                <span class="text-xs text-gray-500">Width</span>
                                <input type="number" class="w-full rounded border-gray-300 text-sm dark:border-white/10 dark:bg-gray-800" wire:model.live.debounce.200ms="elements.{{ $selectedIndex }}.w" />
                            </label>
                            <label class="space-y-1">
                                <span class="text-xs text-gray-500">Height</span>
                                <input type="number" class="w-full rounded border-gray-300 text-sm dark:border-white/10 dark:bg-gray-800" wire:model.live.debounce.200ms="elements.{{ $selectedIndex }}.h" />
                            </label>
                        </div>

                        @if (($selected['type'] ?? null) === 'text')
                            <label class="space-y-1 block">
                                <span class="text-xs text-gray-500">Data source</span>
                                <select
                                    class="w-full rounded border-gray-300 text-sm dark:border-white/10 dark:bg-gray-800"
                                    wire:change="updateSelectedElementBind($event.target.value || null)"
                                >
                                    <option value="" @selected(! \Tapp\FilamentCertificateBuilder\Support\CertificateLayout::isLiveBind($selected['bind'] ?? null, $tokenSet))>Static text</option>
                                    @foreach ($tokenOptions as $tokenKey => $tokenLabel)
                                        <option value="{{ $tokenKey }}" @selected(($selected['bind'] ?? null) === $tokenKey)>{{ $tokenLabel }}</option>
                                    @endforeach
                                </select>
                            </label>
                            @if (\Tapp\FilamentCertificateBuilder\Support\CertificateLayout::isLiveBind($selected['bind'] ?? null, $tokenSet))
                                <p class="rounded-lg bg-gray-50 px-3 py-2 text-xs text-gray-600 dark:bg-white/5 dark:text-gray-300">
                                    This text is filled from live certificate data
                                    (<span class="font-medium">{{ \Tapp\FilamentCertificateBuilder\Support\CertificateLayout::tokenLabel((string) $selected['bind'], $tokenSet) }}</span>)
                                    and cannot be edited here.
                                </p>
                            @else
                                <label class="space-y-1 block">
                                    <span class="text-xs text-gray-500">Text</span>
                                    <textarea
                                        rows="3"
                                        class="w-full rounded border-gray-300 text-sm dark:border-white/10 dark:bg-gray-800"
                                        wire:model.live.debounce.200ms="elements.{{ $selectedIndex }}.text"
                                    ></textarea>
                                </label>
                            @endif
                            <label class="space-y-1 block">
                                <span class="text-xs text-gray-500">Font size</span>
                                <input type="number" class="w-full rounded border-gray-300 text-sm dark:border-white/10 dark:bg-gray-800" wire:model.live.debounce.200ms="elements.{{ $selectedIndex }}.fontSize" />
                            </label>
                            <label class="space-y-1 block">
                                <span class="text-xs text-gray-500">Align</span>
                                <select class="w-full rounded border-gray-300 text-sm dark:border-white/10 dark:bg-gray-800" wire:model.live="elements.{{ $selectedIndex }}.align">
                                    @foreach (['left', 'center', 'right'] as $align)
                                        <option value="{{ $align }}">{{ ucfirst($align) }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label class="space-y-1 block">
                                <span class="text-xs text-gray-500">Weight</span>
                                <select class="w-full rounded border-gray-300 text-sm dark:border-white/10 dark:bg-gray-800" wire:model.live="elements.{{ $selectedIndex }}.fontWeight">
                                    @foreach (['400' => 'Normal', '700' => 'Bold', '800' => 'Extra bold'] as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label class="space-y-1 block">
                                <span class="text-xs text-gray-500">Style</span>
                                <select class="w-full rounded border-gray-300 text-sm dark:border-white/10 dark:bg-gray-800" wire:model.live="elements.{{ $selectedIndex }}.fontStyle">
                                    @foreach (['normal', 'italic'] as $style)
                                        <option value="{{ $style }}">{{ ucfirst($style) }}</option>
                                    @endforeach
                                </select>
                            </label>
                        @endif

                        @if (($selected['type'] ?? null) === 'signature')
                            <label class="space-y-1 block">
                                <span class="text-xs text-gray-500">Signer name</span>
                                <input type="text" class="w-full rounded border-gray-300 text-sm dark:border-white/10 dark:bg-gray-800" wire:model.live.debounce.200ms="elements.{{ $selectedIndex }}.name" />
                            </label>
                            <label class="space-y-1 block">
                                <span class="text-xs text-gray-500">Signer title</span>
                                <input type="text" class="w-full rounded border-gray-300 text-sm dark:border-white/10 dark:bg-gray-800" wire:model.live.debounce.200ms="elements.{{ $selectedIndex }}.title" />
                            </label>
                        @endif
                    </div>
                @endif
            @else
                <p class="text-sm text-gray-500">Select an element on the canvas to edit its position and style.</p>
            @endif

            <div class="mt-6 border-t border-gray-100 pt-4 dark:border-white/10">
                <h5 class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500">Layers</h5>
                <ul class="space-y-1">
                    @foreach ($elements as $element)
                        <li>
                            <button
                                type="button"
                                class="w-full rounded px-2 py-1.5 text-left text-sm hover:bg-gray-50 dark:hover:bg-white/5 {{ $selectedId === $element['id'] ? 'bg-primary-50 text-primary-700 dark:bg-primary-500/10' : '' }}"
                                wire:click="selectElement('{{ $element['id'] }}')"
                            >
                                {{ $element['label'] ?? $element['id'] }}
                            </button>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</div>

@script
<script>
    Alpine.data('certificateDesigner', (config) => ({
        scale: config.scale,
        width: config.width,
        height: config.height,
        dragging: null,
        resizing: null,
        minSize: 20,

        startDrag(event, id) {
            if (this.resizing) return

            const el = event.currentTarget
            this.dragging = {
                id,
                startX: event.clientX,
                startY: event.clientY,
                origX: parseInt(el.style.left || '0', 10),
                origY: parseInt(el.style.top || '0', 10),
                el,
            }

            const onMove = (e) => {
                if (! this.dragging) return
                const dx = (e.clientX - this.dragging.startX) / this.scale
                const dy = (e.clientY - this.dragging.startY) / this.scale
                const x = Math.max(0, Math.round(this.dragging.origX + dx))
                const y = Math.max(0, Math.round(this.dragging.origY + dy))
                this.dragging.el.style.left = x + 'px'
                this.dragging.el.style.top = y + 'px'
                this.dragging.next = { x, y }
            }

            const onUp = () => {
                window.removeEventListener('mousemove', onMove)
                window.removeEventListener('mouseup', onUp)
                if (this.dragging?.next) {
                    config.onMove(this.dragging.id, this.dragging.next)
                }
                this.dragging = null
            }

            window.addEventListener('mousemove', onMove)
            window.addEventListener('mouseup', onUp)
        },

        startResize(event, id, corner) {
            const el = event.currentTarget.parentElement
            this.resizing = {
                id,
                corner,
                startX: event.clientX,
                startY: event.clientY,
                origX: parseInt(el.style.left || '0', 10),
                origY: parseInt(el.style.top || '0', 10),
                origW: parseInt(el.style.width || '0', 10),
                origH: parseInt(el.style.height || '0', 10),
                el,
            }

            const onMove = (e) => {
                if (! this.resizing) return

                const dx = (e.clientX - this.resizing.startX) / this.scale
                const dy = (e.clientY - this.resizing.startY) / this.scale
                let { origX: x, origY: y, origW: w, origH: h, corner } = this.resizing

                if (corner.includes('e')) {
                    w = Math.max(this.minSize, Math.round(this.resizing.origW + dx))
                }

                if (corner.includes('s')) {
                    h = Math.max(this.minSize, Math.round(this.resizing.origH + dy))
                }

                if (corner.includes('w')) {
                    w = Math.max(this.minSize, Math.round(this.resizing.origW - dx))
                    x = Math.max(0, Math.round(this.resizing.origX + (this.resizing.origW - w)))
                }

                if (corner.includes('n')) {
                    h = Math.max(this.minSize, Math.round(this.resizing.origH - dy))
                    y = Math.max(0, Math.round(this.resizing.origY + (this.resizing.origH - h)))
                }

                this.resizing.el.style.left = x + 'px'
                this.resizing.el.style.top = y + 'px'
                this.resizing.el.style.width = w + 'px'
                this.resizing.el.style.height = h + 'px'
                this.resizing.next = { x, y, w, h }
            }

            const onUp = () => {
                window.removeEventListener('mousemove', onMove)
                window.removeEventListener('mouseup', onUp)
                if (this.resizing?.next) {
                    config.onMove(this.resizing.id, this.resizing.next)
                }
                this.resizing = null
            }

            window.addEventListener('mousemove', onMove)
            window.addEventListener('mouseup', onUp)
        },
    }))
</script>
@endscript
