<x-filament-panels::page>
    <div class="space-y-6">
        <div class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-900">
            <p class="text-sm text-gray-500">Current plan</p>
            <p class="mt-1 text-2xl font-semibold capitalize">{{ $this->plan() }}</p>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                @if ($this->onTrial())
                    This salon is on a Pro trial. Subscribing records a Cashier subscription.
                @else
                    Basic includes up to 3 staff members. Pro removes that limit and enables the AI booking assistant.
                @endif
            </p>
            <p class="mt-2 text-xs uppercase tracking-wide text-gray-400">Billing driver: {{ $this->driver() }}</p>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <div class="rounded-xl border border-gray-200 p-6 dark:border-gray-700">
                <h2 class="text-lg font-semibold">Basic</h2>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">Up to 3 staff members. Booking, schedules, and the admin panel.</p>
                <x-filament::button class="mt-4" wire:click="subscribe('basic')" color="gray">
                    Choose Basic
                </x-filament::button>
            </div>
            <div class="rounded-xl border border-rose-200 p-6 dark:border-rose-900">
                <h2 class="text-lg font-semibold">Pro</h2>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">Unlimited staff and the natural-language booking assistant.</p>
                <x-filament::button class="mt-4" wire:click="subscribe('pro')">
                    Choose Pro
                </x-filament::button>
            </div>
        </div>
    </div>
</x-filament-panels::page>
