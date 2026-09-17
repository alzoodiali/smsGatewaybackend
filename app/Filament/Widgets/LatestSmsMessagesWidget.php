<?php

namespace App\Filament\Widgets;

use App\Models\SmsMessage;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestSmsMessagesWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'أحدث رسائل SMS';

    public function table(Table $table): Table
    {
        return $table
            ->query(SmsMessage::query()->latest()->limit(5))
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('phone_number')->label('رقم الهاتف'),
                Tables\Columns\TextColumn::make('message')->label('الرسالة')->limit(40),
                Tables\Columns\TextColumn::make('type')->label('النوع'),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('الحالة')
                    ->colors([
                        'warning' => 'pending',
                        'info' => 'processing',
                        'success' => 'sent',
                        'danger' => 'failed',
                    ]),
                Tables\Columns\TextColumn::make('created_at')->label('تاريخ الإنشاء')->dateTime(),
            ]);
    }
}
