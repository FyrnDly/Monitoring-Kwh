<x-filament-panels::page>
    <x-filament-panels::form wire:submit="filter" id="filter">
        {{ $this->form }}

        <x-filament-panels::form.actions :actions="$this->getFormActions()" />
    </x-filament-panels::form>

    <livewire:monitoring-stat :data="$raw"/>
    @if ($raw['period'] == 'year')
        <livewire:year-monitoring-chart :data="$raw"/>
    @elseif ($raw['period'] == 'month')
        <livewire:month-monitoring-chart :data="$raw"/>
    @elseif ($raw['period'] == 'day')
        <livewire:day-monitoring-chart :data="$raw"/>
    @endif
</x-filament-panels::page>
