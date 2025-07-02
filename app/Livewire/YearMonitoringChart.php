<?php

namespace App\Livewire;

use App\Models\Log;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class YearMonitoringChart extends ChartWidget
{
    protected static ?string $heading = 'Monitoring Kwh Tahunan';
    protected static ?string $pollingInterval = null;
    protected static bool $isLazy = false;
    public ?array $data = [];
    public ?array $month = [
        '01' => 'Januari',
        '02' => 'Februari',
        '03' => 'Maret',
        '04' => 'April',
        '05' => 'Mei',
        '06' => 'Juni',
        '07' => 'Juli',
        '08' => 'Agustus',
        '09' => 'September',
        '10' => 'Oktober',
        '11' => 'November',
        '12' => 'Desember'
    ];

    protected function getData(): array
    {
        $data = $this->data;
        $year = $data['year'];
        $deviceId = $data['device_id'];

        // Hitung batas bulan jika tahun berjalan
        $maxMonth = ($year == date('Y')) ? date('n') : 12;

        // Ambil key bulan dan label sesuai cutoff
        $monthKey = collect($this->month)
            ->filter(fn ($_, $key) => (int)$key <= $maxMonth)
            ->keys();
        $monthLabel = collect($this->month)
            ->filter(fn ($_, $key) => (int)$key <= $maxMonth)
            ->values()
            ->all();

        // Siapkan default kosong untuk semua bulan (agar tidak hilang saat tidak ada data)
        $emptyMap = $monthKey->mapWithKeys(fn ($key) => [
            $key => [
                'ampere' => 0,
                'power' => 0,
                'energy' => 0,
                'frequency' => 0,
                'power_factor' => 0,
                'temperature' => 0,
                'humidity' => 0,
            ]
        ]);

        // Ambil data log dan agregasi berdasarkan bulan
        $monthlyStats = Log::selectRaw('
                DATE_FORMAT(time_stamp, "%m") as month,
                SUM(ampere) as ampere,
                SUM(power) as power,
                AVG(energy) as energy,
                SUM(frequency) as frequency,
                SUM(power_factor) as power_factor,
                AVG(temperature) as temperature,
                AVG(humidity) as humidity
            ')
            ->whereYear('time_stamp', $year)
            ->where('device_id', $deviceId)
            ->whereRaw('MONTH(time_stamp) <= ?', [$maxMonth])
            ->groupBy(DB::raw('DATE_FORMAT(time_stamp, "%m")'))
            ->get()
            ->mapWithKeys(fn ($item) => [
                $item->month => $item->toArray()
            ]);

        // Gabungkan hasil per bulan, isi nol jika kosong
        $monthData = $emptyMap->mapWithKeys(fn ($values, $month) => [
            $month => array_merge($values, $monthlyStats[$month] ?? [])
        ]);

        return [
            'labels' => $monthLabel,
            'datasets' => [
                [
                    'label' => 'Ampere (A)',
                    'data' => $monthData->pluck('ampere')->values()->all(),
                    'borderColor' => '#FFDF3F',
                    'backgroundColor' => '#FFDF3F'
                ],
                [
                    'label' => 'Daya (W)',
                    'data' => $monthData->pluck('power')->values()->all(),
                    'borderColor' => '#72C5FF',
                    'backgroundColor' => '#72C5FF'
                ],
                [
                    'label' => 'Energi (kWh)',
                    'data' => $monthData->pluck('energy')->values()->all(),
                    'borderColor' => '#FF916B',
                    'backgroundColor' => '#FF916B'
                ],
                [
                    'label' => 'Frekuensi (Hz)',
                    'data' => $monthData->pluck('frequency')->values()->all(),
                    'borderColor' => '#9CE358',
                    'backgroundColor' => '#9CE358'
                ],
                [
                    'label' => 'Faktor Daya',
                    'data' => $monthData->pluck('power_factor')->values()->all(),
                    'borderColor' => '#9CE358',
                    'backgroundColor' => '#9CE358'
                ],
                [
                    'label' => 'Suhu (°C)',
                    'data' => $monthData->pluck('temperature')->values()->all(),
                    'borderColor' => '#2262B7',
                    'backgroundColor' => '#2262B7'
                ],
                [
                    'label' => 'Kelembapan (%)',
                    'data' => $monthData->pluck('humidity')->values()->all(),
                    'borderColor' => '#0D307A',
                    'backgroundColor' => '#0D307A'
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
