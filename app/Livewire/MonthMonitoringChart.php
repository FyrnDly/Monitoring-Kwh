<?php

namespace App\Livewire;

use App\Models\Log;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class MonthMonitoringChart extends ChartWidget
{
    protected static ?string $heading = 'Monitoring Kwh Bulanan';
    protected static ?string $pollingInterval = null;
    protected static bool $isLazy = false;

    public ?array $data = [];

    protected function getData(): array
    {
        $data = $this->data;
        $month = (int) $data['month'];
        $year = (int) $data['year'];
        $deviceId = $data['device_id'];

        $now = now();
        $start = Carbon::create($year, $month, 1)->startOfDay();
        $end = ($year === $now->year && $month === $now->month)
            ? $now->endOfDay()
            : Carbon::create($year, $month)->endOfMonth()->endOfDay();

        // 🗓 Buat daftar tanggal lengkap di bulan tersebut
        $dayRange = collect(range(1, $end->day))
            ->map(fn ($d) => str_pad($d, 2, '0', STR_PAD_LEFT)); // '01' s.d. '31'

        $defaultDays = $dayRange->mapWithKeys(fn ($day) => [
            $day => [
                'ampere' => 0,
                'power' => 0,
                'energy' => 0,
                'frequency' => 0,
                'power_factor' => 0,
                'temperature' => 0,
                'humidity' => 0,
            ]
        ]);

        // Diperbaiki menggunakan AVG dan (MAX - MIN) untuk energi harian
        $dailyStats = Log::selectRaw('
                DATE_FORMAT(time_stamp, "%d") as day,
                AVG(ampere) as ampere,
                AVG(power) as power,
                (MAX(energy) - MIN(energy)) as energy,
                AVG(frequency) as frequency,
                AVG(power_factor) as power_factor,
                AVG(temperature) as temperature,
                AVG(humidity) as humidity
            ')
            ->where('device_id', $deviceId)
            ->whereBetween('time_stamp', [$start, $end])
            ->groupBy(DB::raw('DATE_FORMAT(time_stamp, "%d")'))
            ->get()
            ->mapWithKeys(fn ($item) => [str_pad($item->day, 2, '0', STR_PAD_LEFT) => $item->toArray()]);

        // Gabungkan nilai harian dan default
        $finalData = $defaultDays->mapWithKeys(fn ($defaults, $day) => [
            $day => array_merge($defaults, $dailyStats[$day] ?? [])
        ]);

        return [
            'labels' => $dayRange->all(), // ['01', ..., '31']
            'datasets' => [
                [
                    'label' => 'Ampere (A)',
                    'data' => $finalData->pluck('ampere')->map(fn($v) => round($v, 2))->values()->all(),
                    'borderColor' => '#FFDF3F',
                    'backgroundColor' => '#FFDF3F',
                ],
                [
                    'label' => 'Daya (W)',
                    'data' => $finalData->pluck('power')->map(fn($v) => round($v, 2))->values()->all(),
                    'borderColor' => '#72C5FF',
                    'backgroundColor' => '#72C5FF',
                ],
                [
                    'label' => 'Energi (kWh)',
                    'data' => $finalData->pluck('energy')->map(fn($v) => round($v, 2))->values()->all(),
                    'borderColor' => '#FF916B',
                    'backgroundColor' => '#FF916B',
                ],
                [
                    'label' => 'Frekuensi (Hz)',
                    'data' => $finalData->pluck('frequency')->map(fn($v) => round($v, 2))->values()->all(),
                    'borderColor' => '#9CE358',
                    'backgroundColor' => '#9CE358',
                ],
                [
                    'label' => 'Faktor Daya',
                    'data' => $finalData->pluck('power_factor')->map(fn($v) => round($v, 2))->values()->all(),
                    'borderColor' => '#C181FF',
                    'backgroundColor' => '#C181FF',
                ],
                [
                    'label' => 'Suhu (°C)',
                    'data' => $finalData->pluck('temperature')->map(fn($v) => round($v, 2))->values()->all(),
                    'borderColor' => '#2262B7',
                    'backgroundColor' => '#2262B7',
                ],
                [
                    'label' => 'Kelembapan (%)',
                    'data' => $finalData->pluck('humidity')->map(fn($v) => round($v, 2))->values()->all(),
                    'borderColor' => '#0D307A',
                    'backgroundColor' => '#0D307A',
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
