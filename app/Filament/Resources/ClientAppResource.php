<?php

namespace App\Filament\Resources;

use App\Models\ClientApp;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class ClientAppResource extends Resource
{
    protected static ?string $model = ClientApp::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static \UnitEnum|string|null $navigationGroup = 'إدارة النظام';

    protected static ?string $modelLabel = 'تطبيق عميل';

    protected static ?string $pluralModelLabel = 'تطبيقات العملاء';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('app_name')
                    ->label('اسم التطبيق')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('app_id')
                    ->label('معرف التطبيق (App ID)')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Toggle::make('is_active')
                    ->label('نشط')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('app_name')->label('اسم التطبيق')->searchable(),
                Tables\Columns\TextColumn::make('app_id')->label('App ID')->searchable(),
                Tables\Columns\IconColumn::make('is_active')->label('الحالة')->boolean(),
                Tables\Columns\TextColumn::make('created_at')->label('تاريخ الإنشاء')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')->label('الحالة'),
            ])
            ->actions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ClientAppResource\Pages\ListClientApps::route('/'),
            'create' => ClientAppResource\Pages\CreateClientApp::route('/create'),
            'edit' => ClientAppResource\Pages\EditClientApp::route('/{record}/edit'),
        ];
    }
}
