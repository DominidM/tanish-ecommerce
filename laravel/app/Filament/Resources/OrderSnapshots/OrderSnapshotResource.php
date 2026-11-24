<?php

namespace App\Filament\Resources\OrderSnapshots;

use App\Filament\Resources\OrderSnapshots\Pages\ListOrderSnapshots;
use App\Filament\Resources\OrderSnapshots\Pages\ViewOrderSnapshot;
use App\Filament\Resources\OrderSnapshots\Schemas\OrderSnapshotInfolist;
use App\Filament\Resources\OrderSnapshots\Tables\OrderSnapshotsTable;
use App\Models\OrderSnapshot;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class OrderSnapshotResource extends Resource
{
    protected static ?string $model = OrderSnapshot::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    protected static ?string $navigationLabel = 'Pedidos';

    protected static ?string $modelLabel = 'pedido';

    protected static ?string $pluralModelLabel = 'pedidos';

    protected static ?int $navigationSort = 20;

    public static function infolist(Schema $schema): Schema
    {
        return OrderSnapshotInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OrderSnapshotsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrderSnapshots::route('/'),
            'view' => ViewOrderSnapshot::route('/{record}'),
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
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }
}
