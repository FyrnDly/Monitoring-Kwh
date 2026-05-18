<?php

namespace App\Livewire;

use App\Models\Log;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class DayMonitoringChart extends ChartWidget
{
    protected static ?string $heading = 'Monitoring Kwh Harian';
    protected static ?string $pollingInterval = null;
    protected static bool $isLazy = false;

    public ?array $data = [];

    protected function getData(): array
    {
        $deviceId = $this->data['device_id'] ?? null;

        // 🔍 Parse tanggal fleksibel
        $start = $this->parseDate($this->data['start'])->startOfDay();
        $end = $this->parseDate($this->data['end'])->endOfDay();

        $isSingleDay = $start->toDateString() === $end->toDateString();

        if ($isSingleDay) {
            // 🕐 Per jam
            $hourKeys = collect(range(0, 23))->map(fn ($h) => str_pad($h, 2, '0', STR_PAD_LEFT));

            $empty = $hourKeys->mapWithKeys(fn ($hour) => [
                $hour => $this->emptyMetrics()
            ]);

            // Diperbaiki menggunakan AVG dan (MAX - MIN) untuk energi
            $stats = Log::selectRaw('
                    DATE_FORMAT(time_stamp, "%H") as hour,
                    AVG(ampere) as ampere,
                    AVG(power) as power,
                    (MAX(energy) - MIN(energy)) as energy,
                    AVG(frequency) as frequency,
                    AVG(power_factor) as power_factor,
                    AVG(temperature) as temperature,
                    AVG(humidity) as humidity
                ')
                ->where('device_id', $deviceId)
                ->whereDate('time_stamp', $start)
                ->groupBy(DB::raw('DATE_FORMAT(time_stamp, "%H")'))
                ->get()
                ->mapWithKeys(fn ($row) => [$row->hour => $row->toArray()]);

            $final = $empty->mapWithKeys(fn ($base, $hour) => [
                $hour => array_merge($base, $stats[$hour] ?? [])
            ]);

            return $this->toChartResponse($final, $hourKeys->all(), 'H');
        }

        // 📆 Per tanggal
        $dates = collect();
        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            $dates->push($cursor->format('Y-m-d'));
            $cursor->addDay();
        }

        $empty = $dates->mapWithKeys(fn ($date) => [
            $date => $this->emptyMetrics()
        ]);

        // Diperbaiki menggunakan AVG dan (MAX - MIN) untuk energi
        $stats = Log::selectRaw('
                DATE_FORMAT(time_stamp, "%Y-%m-%d") as full_date,
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
            ->groupBy(DB::raw('DATE_FORMAT(time_stamp, "%Y-%m-%d")'))
            ->get()
            ->mapWithKeys(fn ($row) => [$row->full_date => $row->toArray()]);

        $final = $empty->mapWithKeys(fn ($base, $date) => [
            $date => array_merge($base, $stats[$date] ?? [])
        ]);

        return $this->toChartResponse($final, $dates->map(fn ($d) => Carbon::parse($d)->format('d M'))->values()->all(), 'D');
    }

    protected function getType(): string
    {
        return 'line';
    }

    private function parseDate(string $value): Carbon
    {
        $formats = ['Y-m-d', 'd/m/Y', 'd-m-Y'];
        foreach ($formats as $format) {
            try {
                return Carbon::createFromFormat($format, $value);
            } catch (\Exception) {
                continue;
            }
        }
        return Carbon::parse($value); // fallback
    }

    private function emptyMetrics(): array
    {
        return [
            'ampere' => 0,
            'power' => 0,
            'energy' => 0,
            'frequency' => 0,
            'power_factor' => 0,
            'temperature' => 0,
            'humidity' => 0,
        ];
    }

    private function toChartResponse($data, $labels, string $mode): array
    {
        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Ampere (A)',
                    'data' => $data->pluck('ampere')->map(fn($v) => round($v, 2))->values()->all(),
                    'borderColor' => '#FFDF3F',
                    'backgroundColor' => '#FFDF3F',
                ],
                [
                    'label' => 'Daya (W)',
                    'data' => $data->pluck('power')->map(fn($v) => round($v, 2))->values()->all(),
                    'borderColor' => '#72C5FF',
                    'backgroundColor' => '#72C5FF',
                ],
                [
                    'label' => 'Energi (kWh)',
                    'data' => $data->pluck('energy')->map(fn($v) => round($v, 2))->values()->all(),
                    'borderColor' => '#FF916B',
                    'backgroundColor' => '#FF916B',
                ],
                [
                    'label' => 'Frekuensi (Hz)',
                    'data' => $data->pluck('frequency')->map(fn($v) => round($v, 2))->values()->all(),
                    'borderColor' => '#9CE358',
                    'backgroundColor' => '#9CE358',
                ],
                [
                    'label' => 'Faktor Daya',
                    'data' => $data->pluck('power_factor')->map(fn($v) => round($v, 2))->values()->all(),
                    'borderColor' => '#C181FF',
                    'backgroundColor' => '#C181FF',
                ],
                [
                    'label' => 'Suhu (°C)',
                    'data' => $data->pluck('temperature')->map(fn($v) => round($v, 2))->values()->all(),
                    'borderColor' => '#2262B7',
                    'backgroundColor' => '#2262B7',
                ],
                [
                    'label' => 'Kelembapan (%)',
                    'data' => $data->pluck('humidity')->map(fn($v) => round($v, 2))->values()->all(),
                    'borderColor' => '#0D307A',
                    'backgroundColor' => '#0D307A',
                ],
            ]
        ];
    }
}
