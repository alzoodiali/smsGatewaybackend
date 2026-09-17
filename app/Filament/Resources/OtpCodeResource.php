<?php

namespace App\Filament\Resources;

use App\Models\OtpCode;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class OtpCodeResource extends Resource
{
    protected static ?string $model = OtpCode::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-key';

    protected static \UnitEnum|string|null $navigationGroup = 'إدارة الرسائل';

    protected static ?string $modelLabel = 'رمز OTP';

    protected static ?string $pluralModelLabel = 'رموز OTP';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('phone')->label('رقم الهاتف')->required(),
                Forms\Components\TextInput::make('code_hash')->label('رمز OTP المشفر')->required(),
                Forms\Components\DateTimePicker::make('expires_at')->label('تاريخ الانتهاء')->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('phone')->label('رقم الهاتف')->searchable(),
                Tables\Columns\TextColumn::make('request_id')->label('معرف الطلب'),
                Tables\Columns\TextColumn::make('expires_at')->label('تاريخ الانتهاء')->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('created_at')->label('تاريخ الإنشاء')->dateTime()->sortable(),
            ])
            ->actions([
                Actions\ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => OtpCodeResource\Pages\ListOtpCodes::route('/'),
        ];
    }
}
