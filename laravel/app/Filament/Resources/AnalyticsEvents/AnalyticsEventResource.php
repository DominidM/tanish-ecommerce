<?php

namespace App\Filament\Resources\AnalyticsEvents;

use App\Filament\Resources\AnalyticsEvents\Pages\ListAnalyticsEvents;
use App\Filament\Resources\AnalyticsEvents\Pages\ViewAnalyticsEvent;
use App\Filament\Resources\AnalyticsEvents\Schemas\AnalyticsEventInfolist;
use App\Filament\Resources\AnalyticsEvents\Tables\AnalyticsEventsTable;
use App\Models\AnalyticsEvent;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class AnalyticsEventResource extends Resource
{
    protected static ?string $model = AnalyticsEvent::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?string $navigationLabel = 'Analítica';

    protected static ?string $modelLabel = 'evento';

    protected static ?string $pluralModelLabel = 'eventos';

    protected static ?int $navigationSort = 60;

    public static function infolist(Schema $schema): Schema
    {
        return AnalyticsEventInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AnalyticsEventsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAnalyticsEvents::route('/'),
            'view' => ViewAnalyticsEvent::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }
}
