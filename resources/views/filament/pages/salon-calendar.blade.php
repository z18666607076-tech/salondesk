<x-filament-panels::page>
    <div class="space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap gap-2">
                <x-filament::button size="sm" color="gray" wire:click="shift(-1)">
                    {{ __('calendar.previous') }}
                </x-filament::button>
                <x-filament::button size="sm" color="gray" wire:click="today">
                    {{ __('calendar.today') }}
                </x-filament::button>
                <x-filament::button size="sm" color="gray" wire:click="shift(1)">
                    {{ __('calendar.next') }}
                </x-filament::button>
            </div>
            <p class="text-base font-semibold text-gray-950 dark:text-white">{{ $this->heading() }}</p>
            <div class="flex gap-2">
                <x-filament::button size="sm" :color="$mode === 'day' ? 'primary' : 'gray'" wire:click="show('day')">
                    {{ __('calendar.day') }}
                </x-filament::button>
                <x-filament::button size="sm" :color="$mode === 'week' ? 'primary' : 'gray'" wire:click="show('week')">
                    {{ __('calendar.week') }}
                </x-filament::button>
            </div>
        </div>

        @if ($mode === 'day')
            <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
                <div
                    class="grid min-w-[640px]"
                    style="grid-template-columns: 4.5rem repeat({{ max($this->staffColumns()->count(), 1) }}, minmax(9rem, 1fr));"
                >
                    <div class="border-b border-gray-200 bg-gray-50 px-2 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:border-gray-700 dark:bg-gray-800"></div>
                    @foreach ($this->staffColumns() as $column)
                        <div class="border-b border-l border-gray-200 bg-gray-50 px-3 py-3 text-sm font-semibold text-gray-950 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                            {{ $column->name }}
                        </div>
                    @endforeach

                    @foreach ($this->dayRows() as $row)
                        <div class="border-b border-gray-100 px-2 py-3 text-xs text-gray-500 dark:border-gray-800">
                            {{ $row['label'] }}
                        </div>
                        @foreach ($row['cells'] as $visits)
                            <div class="min-h-16 border-b border-l border-gray-100 px-2 py-2 dark:border-gray-800">
                                @forelse ($visits as $visit)
                                    <div class="mb-1 rounded-lg border border-rose-200 bg-rose-50 px-2 py-1 text-sm dark:border-rose-900 dark:bg-rose-950">
                                        <div class="font-semibold text-gray-950 dark:text-white">{{ $visit['customer'] }}</div>
                                        <div class="text-xs text-gray-600 dark:text-gray-300">{{ $visit['time'] }} · {{ $visit['service'] }}</div>
                                    </div>
                                @empty
                                    <span class="sr-only">{{ __('calendar.empty') }}</span>
                                @endforelse
                            </div>
                        @endforeach
                    @endforeach
                </div>
            </div>
        @else
            <div class="grid gap-3 md:grid-cols-7">
                @foreach ($this->weekDays() as $day)
                    <section class="rounded-xl border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-900">
                        <h2 class="text-sm font-semibold text-gray-950 dark:text-white">{{ $day['label'] }}</h2>
                        <div class="mt-2 space-y-2">
                            @forelse ($day['visits'] as $visit)
                                <article class="rounded-lg border border-rose-200 bg-rose-50 px-2 py-2 text-sm dark:border-rose-900 dark:bg-rose-950">
                                    <div class="font-semibold text-gray-950 dark:text-white">{{ $visit['customer'] }}</div>
                                    <div class="text-xs text-gray-600 dark:text-gray-300">{{ $visit['time'] }}</div>
                                    <div class="text-xs text-gray-600 dark:text-gray-300">{{ $visit['staff'] }} · {{ $visit['service'] }}</div>
                                </article>
                            @empty
                                <p class="text-xs text-gray-400">{{ __('calendar.empty') }}</p>
                            @endforelse
                        </div>
                    </section>
                @endforeach
            </div>
        @endif
    </div>
</x-filament-panels::page>
