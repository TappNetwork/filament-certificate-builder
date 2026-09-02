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
