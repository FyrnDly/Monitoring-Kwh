<?php

namespace App\Livewire;

use Carbon\Carbon;
use App\Models\Log;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;

class MonitoringStat extends BaseWidget
{
    protected static ?string $pollingInterval = null;
    protected static bool $isLazy = false;
    public ?array $data = [];

    protected function getStats(): array
    {
        $data = $this->data;
        $now = now();

        $start = match ($data['period']) {
            'year' => Carbon::create($data['year'], 1, 1)->startOfDay(),
            'month' => Carbon::create($data['year'], $data['month'], 1)->startOfDay(),
            'day' => Carbon::parse($data['start'])->startOfDay()
        };

        $end = match ($data['period']) {
            'year' => $data['year'] == $now->year
                ? $now->endOfDay() // Jika tahun ini, pakai tanggal hari ini
                : Carbon::create($data['year'], 12, 31)->endOfDay(),

            'month' => $data['year'] == $now->year && $data['month'] == $now->format('m')
                ? $now->endOfDay() // Jika bulan berjalan, pakai hari ini
                : Carbon::create($data['year'], $data['month'], 1)->endOfMonth()->endOfDay(),

            'day' => Carbon::parse($data['end'])->endOfDay()
        };

        $datasets = Log::where('device_id', $data['device_id'])
            ->whereBetween('time_stamp', [$start, $end])
            ->selectRaw('
                SUM(ampere) AS ampere,
                SUM(power) AS power,
                AVG(energy) AS energy,
                AVG(frequency) AS frequency,
                SUM(power_factor) AS power_factor,
                AVG(temperature) AS temperature,
                AVG(humidity) AS humidity
            ')->first();

        return [
            Stat::make('Arus (A)', round($datasets->ampere, 2))
                ->description('Total Arus (A) yang mengalir')
                ->color('warning'),
            Stat::make('Daya (W)', round($datasets->ampere, 2))
                ->description('Total Daya (W) yang digunakan')
                ->color('success'),
            Stat::make('Energy (Kwh)', round($datasets->ampere, 2))
                ->description('Rata-rata Energi (Kwh) yang digunakan')
                ->color('info'),
            Stat::make('Frekuensi (Hz)', round($datasets->frequency, 2))
                ->description('Rata-rata frekuensi (Hz) yang digunakan')
                ->color('gray'),
            Stat::make('Suhu (C)', round($datasets->temperature, 2))
                ->description('Rata-rata Suhu Perangkat Selama Monitoring')
                ->color('info'),
            Stat::make('Kelembapan (%)', round($datasets->humidity, 2))
                ->description('Rata-rata Kelembapan Perangkat Selama Monitoring')
                ->color('warning'),
        ];
    }
}
