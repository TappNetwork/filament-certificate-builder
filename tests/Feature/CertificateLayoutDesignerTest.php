<?php

declare(strict_types=1);

use Livewire\Livewire;
use Tapp\FilamentCertificateBuilder\Livewire\CertificateLayoutDesigner;
use Tapp\FilamentCertificateBuilder\Models\CertificateTemplate;

beforeEach(function () {
    $this->template = CertificateTemplate::factory()->create([
        'name' => 'Custom Layout',
    ]);
});

it('can move an element and save the layout', function () {
    Livewire::test(CertificateLayoutDesigner::class, ['template' => $this->template])
        ->call('updateElementPosition', 'logo_1', ['x' => 40, 'y' => 55])
        ->call('save')
        ->assertHasNoErrors();

    $logo = collect($this->template->fresh()->resolvedLayout()['elements'])
        ->firstWhere('id', 'logo_1');

    expect($logo['x'])->toBe(40)
        ->and($logo['y'])->toBe(55);
});

it('can resize an element by updating width and height', function () {
    Livewire::test(CertificateLayoutDesigner::class, ['template' => $this->template])
        ->call('selectElement', 'logo_1')
        ->call('updateElementPosition', 'logo_1', ['x' => 100, 'y' => 40, 'w' => 420, 'h' => 160])
        ->call('save')
        ->assertHasNoErrors();

    $logo = collect($this->template->fresh()->resolvedLayout()['elements'])
        ->firstWhere('id', 'logo_1');

    expect($logo['w'])->toBe(420)
        ->and($logo['h'])->toBe(160);
});

it('can change the number of signature fields up to three', function () {
    Livewire::test(CertificateLayoutDesigner::class, ['template' => $this->template])
        ->assertSet('signatureCount', 2)
        ->call('setSignatureCount', 3)
        ->assertSet('signatureCount', 3)
        ->call('save')
        ->assertHasNoErrors();

    $layout = $this->template->fresh()->resolvedLayout();
    $signatures = collect($layout['elements'])->where('type', 'signature');

    expect($layout['signature_count'])->toBe(3)
        ->and($signatures)->toHaveCount(3);
});

it('can save canvas border configuration', function () {
    Livewire::test(CertificateLayoutDesigner::class, ['template' => $this->template])
        ->set('border.style', 'gradient')
        ->set('border.width', 6)
        ->set('border.color', '#a3e635')
        ->set('border.gradient', 'linear-gradient(to right, #a3e635, #0ea5e9, #67e8f9)')
        ->call('save')
        ->assertHasNoErrors();

    $border = $this->template->fresh()->resolvedLayout()['border'];

    expect($border['style'])->toBe('gradient')
        ->and($border['width'])->toBe(6)
        ->and($border['gradient'])->toBe('linear-gradient(to right, #a3e635, #0ea5e9, #67e8f9)');
});

it('can save banner header configuration', function () {
    Livewire::test(CertificateLayoutDesigner::class, ['template' => $this->template])
        ->set('header.enabled', true)
        ->set('header.height', 220)
        ->set('header.title_bind', 'course_name')
        ->set('header.subtitle', 'CERTIFICATE OF COMPLETION')
        ->set('header.title_transform', 'uppercase')
        ->call('save')
        ->assertHasNoErrors();

    $header = $this->template->fresh()->resolvedLayout()['header'];

    expect($header['enabled'])->toBeTrue()
        ->and($header['height'])->toBe(220)
        ->and($header['title_bind'])->toBe('course_name')
        ->and($header['subtitle'])->toBe('CERTIFICATE OF COMPLETION')
        ->and($header['title_transform'])->toBe('uppercase');
});

it('can bind a static text element to a catalog token', function () {
    Livewire::test(CertificateLayoutDesigner::class, ['template' => $this->template])
        ->call('selectElement', 'certifying_line')
        ->call('updateSelectedElementBind', 'course_name')
        ->call('save')
        ->assertHasNoErrors();

    $certifying = collect($this->template->fresh()->resolvedLayout()['elements'])
        ->firstWhere('id', 'certifying_line');

    expect($certifying['bind'])->toBe('course_name');
});
