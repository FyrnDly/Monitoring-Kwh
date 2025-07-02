<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Infolists;
use App\Models\Device;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\DeviceResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\DeviceResource\RelationManagers;

class DeviceResource extends Resource
{
    protected static ?string $model = Device::class;

    protected static ?string $slug = 'perangkat-monitoring';
    protected static ?string $label = 'Perangkat Monitoring';
    protected static ?string $navigationIcon = 'heroicon-o-computer-desktop';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('mac_address')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\TextEntry::make('name')
                    ->label('Nama Perangkat'),
                Infolists\Components\TextEntry::make('mac_address')
                    ->label('Alamat MAC')
                    ->color('primary')
                    ->badge(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Perangkat')
                    ->searchable(isIndividual: true, isGlobal: false),
                Tables\Columns\TextColumn::make('mac_address')
                    ->label('Alamat MAC')
                    ->searchable(isIndividual: true, isGlobal: false)
                    ->color('primary')
                    ->alignCenter()
                    ->badge(),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->color('info'),
                Tables\Actions\EditAction::make()->color('warning'),
                Tables\Actions\DeleteAction::make()->color('gray'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageDevices::route('/'),
        ];
    }
}
