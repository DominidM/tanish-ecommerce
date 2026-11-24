<?php

namespace App\Filament\Resources\ProductSnapshots;

use App\Filament\Resources\ProductSnapshots\Pages\ListProductSnapshots;
use App\Filament\Resources\ProductSnapshots\Pages\ViewProductSnapshot;
use App\Filament\Resources\ProductSnapshots\Schemas\ProductSnapshotInfolist;
use App\Filament\Resources\ProductSnapshots\Tables\ProductSnapshotsTable;
use App\Models\ProductSnapshot;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ProductSnapshotResource extends Resource
{
    protected static ?string $model = ProductSnapshot::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCube;

    protected static ?string $navigationLabel = 'Productos';

    protected static ?string $modelLabel = 'producto';

    protected static ?string $pluralModelLabel = 'productos';

    protected static ?int $navigationSort = 10;

    public static function infolist(Schema $schema): Schema
    {
        return ProductSnapshotInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProductSnapshotsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProductSnapshots::route('/'),
            'view' => ViewProductSnapshot::route('/{record}'),
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
