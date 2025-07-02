<?php

namespace App\Filament\Pages;

use Filament\Forms;
use App\Models\Device;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Support\Exceptions\Halt;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Forms\Concerns\InteractsWithForms;

class Log extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $view = 'filament.pages.log';
    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';
    protected static ?string $title = 'Monitoring Kwh';
    protected static ?string $slug = 'monitoring-kwh';

    protected static bool $isLazy = false;
    public ?array $data = [];
    public ?array $raw = [];
    public ?array $currentMonth = [];
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

    public function mount(): void {
        $this->data = session('data') ?? [
            'device_id' => Device::first()?->id,
            'period' => 'year',
            'year' => date('Y'),
            'month' => date('m'),
            'start' => now(),
            'end' => now(),
        ];

        $this->raw = $this->data;
        $this->form->fill($this->data);

        // Filter bulan sesuai tahun aktif
        $currentMonth = date('m');
        $this->currentMonth = array_filter(
            $this->month,
            fn ($key) => $key <= $currentMonth,
            ARRAY_FILTER_USE_KEY
        );
    }

    public function form(Form $form): Form {
        return $form
            ->statePath('data')
            ->schema([
                Forms\Components\Section::make('Filter Berdasarkan')
                    ->columns(1)
                    ->schema([
                        Forms\Components\Select::make('device_id')
                            ->label('Perangkat')
                            ->options(fn () => Device::pluck('name', 'id')->toArray())
                            ->native(false)
                            ->searchable(),
                        Forms\Components\Fieldset::make('Periode Waktu')
                            ->columnSpan(['md' => 2])
                            ->schema([
                                Forms\Components\Select::make('period')
                                    ->hiddenLabel()
                                    ->options([
                                        'year' => 'Tahun',
                                        'month' => 'Bulan',
                                        'day' => 'Hari',
                                    ])->live()
                                    ->native(false)
                                    ->columnSpanFull(),
                                Forms\Components\Select::make('year')
                                    ->live()
                                    ->hiddenLabel()
                                    ->placeholder('Pilih Tahun Monitoring')
                                    ->default($this->data['year'] ?? date('Y'))
                                    ->columnSpan(fn (Forms\Get $get) => $get('period') == 'month' ? 1 : 2)
                                    ->options(array_combine(range(date('Y') - 2, date('Y')), range(date('Y') - 2, date('Y'))))
                                    ->visible(fn (Forms\Get $get) => $get('period') == 'year' || $get('period') == 'month')
                                    ->afterStateUpdated(fn (Forms\Set $set) => $set('month', null))
                                    ->native(false),
                                Forms\Components\Select::make('month')
                                    ->hiddenLabel()
                                    ->placeholder('Pilih Bulan Monitoring')
                                    ->options(fn (Forms\Get $get) => $get('year') == date('Y') ? $this->currentMonth : $this->month)
                                    ->visible(fn (Forms\Get $get) => $get('period') == 'month')
                                    ->native(false)
                                    ->columnSpan(1),
                                Forms\Components\DatePicker::make('start')
                                    ->live()
                                    ->hiddenLabel()
                                    ->placeholder('Tanggal Mulai Monitoring')
                                    ->visible(fn (Forms\Get $get) => $get('period') == 'day')
                                    ->afterStateUpdated(fn (Forms\Set $set) => $set('end', null))
                                    ->maxDate(now())
                                    ->displayFormat('d m Y')
                                    ->format('Y-m-d')
                                    ->native(false)
                                    ->columnSpan(1),
                                Forms\Components\DatePicker::make('end')
                                    ->hiddenLabel()
                                    ->placeholder('Tanggal Akhir Monitoring')
                                    ->visible(fn (Forms\Get $get) => $get('period') == 'day')
                                    ->maxDate(now())
                                    ->minDate(fn (Forms\Get $get) => $get('start'))
                                    ->displayFormat('d m Y')
                                    ->format('Y-m-d')
                                    ->native(false)
                                    ->columnSpan(1),
                            ]),
                    ])
            ]);
    }

    protected function getFormActions(): array {
        return [
            Action::make('filter')
                ->label(__('Terapkan Filter'))
                ->submit('filter'),
        ];
    }

    public function filter(): void {
        try {
            $data = $this->form->getState();
            redirect()->route('filament.app.pages.monitoring-kwh')->with('data', [
                'device_id' =>  $data['device_id'],
                'period' =>  $data['period'],
                'year' =>  $data['year'] ?? NULL,
                'month' =>  $data['month'] ?? NULL,
                'start' =>  $data['start'] ?? NULL,
                'end' =>  $data['end'] ?? NULL,
            ]);

            Notification::make()
                ->success()
                ->title("Filter Berhasil Diterapkan")
                ->send();
        } catch (Halt $exception) {
            Notification::make()
                ->danger()
                ->title("Filter Gagal: ". $exception->getMessage())
                ->send();
        }
    }
}
