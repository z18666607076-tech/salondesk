<div class="wrap">
    <div class="top">
        <a class="mark" href="{{ url('/') }}">SalonDesk</a>
        <span class="muted">{{ $tenant->timezone }} · {{ $tenant->currency }}</span>
    </div>

    <h1 class="salon">{{ $tenant->name }}</h1>
    <p class="meta">{{ __('booking.book_visit') }}</p>

    @if ($booked)
        <section class="card">
            <h2>{{ __('booking.booked_title') }}</h2>
            <p>{{ $confirmation }}</p>
            <button class="primary" type="button" wire:click="bookAnother">{{ __('booking.another') }}</button>
        </section>
    @else
        <section class="card">
            <h2>{{ __('booking.choose_service') }}</h2>
            <div class="services">
                @foreach ($services as $service)
                    <button
                        type="button"
                        wire:click="$set('serviceId', {{ $service->id }})"
                        class="{{ $serviceId === $service->id ? 'is-selected' : '' }}"
                    >
                        <strong>{{ $service->name }}</strong>
                        <span class="muted">
                            {{ __('booking.minutes', ['count' => $service->duration_minutes]) }}
                            · {{ \App\Support\SalonPreferences::money($service->price_cents, $service->currency ?: $tenant->currency, $tenant->locale) }}
                        </span>
                    </button>
                @endforeach
            </div>
        </section>

        <section class="card">
            <h2>{{ __('booking.choose_when') }}</h2>
            <div class="row two">
                <div>
                    <label for="booking-date">{{ __('booking.date') }}</label>
                    <input id="booking-date" type="date" wire:model.live="date">
                </div>
                <div>
                    <label for="booking-staff">{{ __('booking.staff') }}</label>
                    <select id="booking-staff" wire:change="chooseStaff($event.target.value)">
                        <option value="" @selected($staffId === null)>{{ __('booking.any_staff') }}</option>
                        @foreach ($staffMembers as $member)
                            <option value="{{ $member->id }}" @selected($staffId === $member->id)>{{ $member->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <h3 class="staff-name">{{ __('booking.available_times') }}</h3>
            @error('startsAt')
                <p class="error">{{ $message }}</p>
            @enderror

            @if ($openings->isEmpty())
                <p class="muted">{{ __('booking.no_slots') }}</p>
            @else
                @foreach ($openings->groupBy('staffName') as $staffName => $group)
                    <h3 class="staff-name">{{ $staffName }}</h3>
                    <div class="slots">
                        @foreach ($group as $slot)
                            @php
                                $iso = $slot->startsAt->toIso8601String();
                                $selected = $startsAt === $iso && $staffId === $slot->staffId;
                            @endphp
                            <button
                                type="button"
                                wire:click="selectSlot({{ $slot->staffId }}, '{{ $iso }}')"
                                class="{{ $selected ? 'is-selected' : '' }}"
                            >
                                {{ $slot->startsAt->timezone($tenant->timezone)->format('g:i A') }}
                                @if ($selected)
                                    <span class="muted">{{ __('booking.selected') }}</span>
                                @endif
                            </button>
                        @endforeach
                    </div>
                @endforeach
            @endif
        </section>

        <section class="card">
            <h2>{{ __('booking.your_details') }}</h2>
            <label for="booking-name">{{ __('booking.name') }}</label>
            <input id="booking-name" type="text" wire:model="customerName" autocomplete="name">
            @error('customerName') <p class="error">{{ $message }}</p> @enderror

            <label for="booking-email">{{ __('booking.email') }}</label>
            <input id="booking-email" type="email" wire:model="customerEmail" autocomplete="email">
            @error('customerEmail') <p class="error">{{ $message }}</p> @enderror

            <div class="row two">
                <div>
                    <label for="booking-phone">{{ __('booking.phone') }} <span class="muted">{{ __('booking.optional') }}</span></label>
                    <input id="booking-phone" type="tel" wire:model="customerPhone" autocomplete="tel">
                </div>
                <div>
                    <label for="booking-notes">{{ __('booking.notes') }} <span class="muted">{{ __('booking.optional') }}</span></label>
                    <input id="booking-notes" type="text" wire:model="notes">
                </div>
            </div>

            <button class="primary" type="button" wire:click="book">{{ __('booking.confirm') }}</button>
        </section>

        @if ($this->assistantEnabled())
            <section class="card">
                <h2>{{ __('booking.assistant_title') }}</h2>
                <p class="muted">{{ __('booking.assistant_help') }}</p>
                <label for="booking-assistant">{{ __('booking.assistant_title') }}</label>
                <textarea id="booking-assistant" wire:model="assistantMessage" placeholder="{{ __('booking.assistant_placeholder') }}"></textarea>
                @error('assistantMessage') <p class="error">{{ $message }}</p> @enderror
                <button class="primary" type="button" wire:click="askAssistant">{{ __('booking.assistant_ask') }}</button>

                @if ($proposal)
                    <div class="proposal">
                        <p>{{ $proposal['summary'] }}</p>
                        @if ($proposal['available'])
                            <button class="primary" type="button" wire:click="confirmAssistant">{{ __('booking.assistant_confirm') }}</button>
                        @endif
                    </div>
                @endif
            </section>
        @endif
    @endif

    <footer>SalonDesk</footer>
</div>
