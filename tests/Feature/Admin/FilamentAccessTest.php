<?php

use App\Filament\Resources\Services\Pages\ListServices;
use App\Models\Service;
use App\Tenancy\TenantContext;
use Filament\Facades\Filament;
use Livewire\Livewire;

it('opens the tenant admin for an owner and refuses another salon', function () {
    $glow = salon(['slug' => 'glow-studio']);
    $nails = salon(['slug' => 'northshore-nails']);

    $this->actingAs($glow['owner']);

    $this->get('/admin/glow-studio')->assertOk();
    $this->get('/admin/northshore-nails')->assertNotFound();

    expect($nails['tenant']->is($glow['tenant']))->toBeFalse();
});

it('lists only the current salon services in filament', function () {
    $glow = salon();
    $nails = salon();

    app(TenantContext::class)->set($glow['tenant']);
    $this->actingAs($glow['owner']);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Filament::setTenant($glow['tenant']);

    Livewire::test(ListServices::class)
        ->assertCanSeeTableRecords([$glow['service']])
        ->assertCanNotSeeTableRecords([$nails['service']]);

    expect($nails['service'])->toBeInstanceOf(Service::class);
});
