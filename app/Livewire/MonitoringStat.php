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
                AVG(ampere) AS avg_ampere,
                AVG(power) AS avg_power,
                MAX(energy) - MIN(energy) AS total_energy,
                AVG(frequency) AS avg_frequency,
                AVG(temperature) AS avg_temperature,
                AVG(humidity) AS avg_humidity
            ')->first();

        return [
            Stat::make('Arus Rata-rata (A)', round($datasets->avg_ampere, 2))
                ->description('Beban arus rata-rata dalam periode ini')
                ->color('warning'),
                
            Stat::make('Daya Rata-rata (W)', round($datasets->avg_power, 2))
                ->description('Beban daya rata-rata dalam periode ini')
                ->color('success'),
                
            Stat::make('Total Energy (kWh)', round($datasets->total_energy, 2))
                ->description('Total pemakaian listrik (Selisih Max-Min)')
                ->color('info'),

            Stat::make('Frekuensi (Hz)', round($datasets->avg_frequency, 2))
                ->description('Rata-rata frekuensi listrik')
                ->color('gray'),

            Stat::make('Suhu (C)', round($datasets->avg_temperature, 2))
                ->description('Rata-rata Suhu Perangkat')
                ->color('info'),

            Stat::make('Kelembapan (%)', round($datasets->avg_humidity, 2))
                ->description('Rata-rata Kelembapan Perangkat')
                ->color('warning'),
        ];
    }
}
