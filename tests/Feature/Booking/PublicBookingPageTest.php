<?php

use App\Enums\AppointmentSource;
use App\Livewire\PublicBooking;
use App\Models\Appointment;
use App\Support\SalonPreferences;
use App\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Livewire\Livewire;

beforeEach(function () {
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-05 09:00:00', 'Asia/Singapore'));
    $this->travelTo(CarbonImmutable::now());
});

it('renders a public booking page for the salon', function () {
    $salon = salon(['slug' => 'glow-studio', 'name' => 'Glow Studio']);

    $this->get('/book/glow-studio')
        ->assertOk()
        ->assertSee('Glow Studio')
        ->assertSee('Choose a service')
        ->assertSee('Haircut')
        ->assertSee('Asia/Singapore')
        ->assertSee('Booking assistant')
        ->assertSee(SalonPreferences::money(4800, 'SGD', 'en'), false);

    $this->get('/book/missing-salon')->assertNotFound();
});

it('renders simplified chinese, the salon currency, and the salon timezone', function () {
    $salon = salon([
        'slug' => 'northshore-nails',
        'name' => 'Northshore Nails',
        'locale' => 'zh_CN',
        'currency' => 'CNY',
        'timezone' => 'Asia/Shanghai',
        'trial_ends_at' => null,
    ]);
    $salon['service']->update(['currency' => 'CNY']);

    $this->get('/book/northshore-nails')
        ->assertOk()
        ->assertSee('<html lang="zh-CN">', false)
        ->assertSee('选择服务')
        ->assertSee('Asia/Shanghai')
        ->assertSee('CNY')
        ->assertSee(SalonPreferences::money(4800, 'CNY', 'zh_CN'), false)
        ->assertDontSee('Booking assistant');
});

it('books a visit through the public page and rejects a taken slot', function () {
    $salon = salon();

    Livewire::test(PublicBooking::class, ['tenant' => $salon['tenant']])
        ->set('serviceId', $salon['service']->id)
        ->set('date', '2026-10-06')
        ->call('selectSlot', $salon['staff']->id, '2026-10-06T04:00:00+00:00')
        ->set('customerName', 'Priya Shah')
        ->set('customerEmail', 'priya@example.com')
        ->set('customerPhone', '+65 8000 0000')
        ->call('book')
        ->assertHasNoErrors()
        ->assertSet('booked', true);

    app(TenantContext::class)->set($salon['tenant']);

    $appointment = Appointment::query()->first();

    expect($appointment)->not->toBeNull()
        ->and($appointment->source)->toBe(AppointmentSource::Web)
        ->and($appointment->staff_id)->toBe($salon['staff']->id)
        ->and($appointment->customer->email)->toBe('priya@example.com');

    Livewire::test(PublicBooking::class, ['tenant' => $salon['tenant']])
        ->set('serviceId', $salon['service']->id)
        ->set('date', '2026-10-06')
        ->call('selectSlot', $salon['staff']->id, '2026-10-06T04:00:00+00:00')
        ->set('customerName', 'Other Guest')
        ->set('customerEmail', 'other@example.com')
        ->call('book')
        ->assertHasErrors('startsAt');

    expect(Appointment::query()->count())->toBe(1);
});

it('confirms an assistant proposal from the booking page', function () {
    $salon = salon(['locale' => 'zh_CN']);

    Livewire::test(PublicBooking::class, ['tenant' => $salon['tenant']])
        ->set('customerName', 'Priya Shah')
        ->set('customerEmail', 'priya@example.com')
        ->set('assistantMessage', '明天下午 haircut with Anna')
        ->call('askAssistant')
        ->assertHasNoErrors()
        ->assertSet('proposal.available', true)
        ->call('confirmAssistant')
        ->assertSet('booked', true);

    app(TenantContext::class)->set($salon['tenant']);

    expect(Appointment::query()->first()?->source)->toBe(AppointmentSource::Ai);
});

it('does not let a basic salon use the assistant on the booking page', function () {
    $salon = salon(['trial_ends_at' => null]);

    Livewire::test(PublicBooking::class, ['tenant' => $salon['tenant']])
        ->set('customerName', 'Priya Shah')
        ->set('customerEmail', 'priya@example.com')
        ->set('assistantMessage', 'a haircut with Anna tomorrow afternoon')
        ->call('askAssistant')
        ->assertHasErrors('assistantMessage')
        ->call('confirmAssistant');

    app(TenantContext::class)->set($salon['tenant']);

    expect(Appointment::query()->count())->toBe(0);
});
