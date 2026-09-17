<?php

namespace App\Filament\Resources;

use App\Models\SmsGateway;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class SmsGatewayResource extends Resource
{
    protected static ?string $model = SmsGateway::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-device-phone-mobile';

    protected static \UnitEnum|string|null $navigationGroup = 'إدارة الأجهزة';

    protected static ?string $modelLabel = 'بوابة SMS';

    protected static ?string $pluralModelLabel = 'بوابات SMS';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('device_id')->label('معرف الجهاز')->required(),
                Forms\Components\TextInput::make('name')->label('اسم الجهاز')->required(),
                Forms\Components\TextInput::make('phone_number')->label('رقم الهاتف'),
                Forms\Components\TextInput::make('sim_slot')->label('منفذ الشريحة')->numeric()->default(1),
                Forms\Components\Select::make('status')
                    ->label('الحالة')
                    ->options([
                        'online' => 'متصل',
                        'offline' => 'غير متصل',
                        'busy' => 'مشغول',
                    ])
                    ->default('offline'),
                Forms\Components\TextInput::make('battery_level')->label('مستوى البطارية (%)')->numeric(),
                Forms\Components\TextInput::make('signal_strength')->label('قوة الإشارة')->numeric(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('name')->label('اسم الجهاز')->searchable(),
                Tables\Columns\TextColumn::make('phone_number')->label('رقم الهاتف')->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'online' => 'success',
                        'offline' => 'danger',
                        'busy' => 'warning',
                        default => 'secondary',
                    }),
                Tables\Columns\TextColumn::make('battery_level')->label('البطارية (%)')->suffix('%'),
                Tables\Columns\TextColumn::make('signal_strength')->label('الإشارة'),
                Tables\Columns\TextColumn::make('last_ping')->label('آخر اتصال')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'online' => 'متصل',
                        'offline' => 'غير متصل',
                        'busy' => 'مشغول',
                    ]),
            ])
            ->actions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => SmsGatewayResource\Pages\ListSmsGateways::route('/'),
            'create' => SmsGatewayResource\Pages\CreateSmsGateway::route('/create'),
            'edit' => SmsGatewayResource\Pages\EditSmsGateway::route('/{record}/edit'),
        ];
    }
}
