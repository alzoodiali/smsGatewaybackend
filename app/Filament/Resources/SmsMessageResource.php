<?php

namespace App\Filament\Resources;

use App\Models\SmsMessage;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class SmsMessageResource extends Resource
{
    protected static ?string $model = SmsMessage::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static \UnitEnum|string|null $navigationGroup = 'إدارة الرسائل';

    protected static ?string $modelLabel = 'رسالة SMS';

    protected static ?string $pluralModelLabel = 'رسائل SMS';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('phone')->label('رقم الهاتف')->required(),
                Forms\Components\Textarea::make('message')->label('نص الرسالة')->required(),
                Forms\Components\Select::make('type')
                    ->label('النوع')
                    ->options([
                        'otp' => 'OTP',
                        'notification' => 'إشعار',
                        'custom' => 'عام',
                    ])
                    ->default('otp'),
                Forms\Components\Select::make('status')
                    ->label('الحالة')
                    ->options([
                        'pending' => 'قيد الانتظار',
                        'processing' => 'قيد المعالجة',
                        'sent' => 'تم الإرسال',
                        'failed' => 'فشل',
                    ]),
                Forms\Components\TextInput::make('request_id')->label('معرف الطلب (Request ID)'),
                Forms\Components\TextInput::make('error')->label('رسالة الخطأ'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('phone')->label('رقم الهاتف')->searchable(),
                Tables\Columns\TextColumn::make('message')->label('الرسالة')->limit(30),
                Tables\Columns\TextColumn::make('type')->label('النوع'),
                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'processing' => 'info',
                        'sent' => 'success',
                        'failed' => 'danger',
                        default => 'secondary',
                    }),
                Tables\Columns\TextColumn::make('gateway.name')->label('البوابة'),
                Tables\Columns\TextColumn::make('created_at')->label('تاريخ الإنشاء')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'قيد الانتظار',
                        'processing' => 'قيد المعالجة',
                        'sent' => 'تم الإرسال',
                        'failed' => 'فشل',
                    ]),
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'otp' => 'OTP',
                        'notification' => 'إشعار',
                        'custom' => 'عام',
                    ]),
            ])
            ->actions([
                Actions\ViewAction::make(),
                Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => SmsMessageResource\Pages\ListSmsMessages::route('/'),
            'create' => SmsMessageResource\Pages\CreateSmsMessage::route('/create'),
            'edit' => SmsMessageResource\Pages\EditSmsMessage::route('/{record}/edit'),
        ];
    }
}
