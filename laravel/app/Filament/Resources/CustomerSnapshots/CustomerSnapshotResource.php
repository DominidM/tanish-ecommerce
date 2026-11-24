<?php

namespace App\Filament\Resources\CustomerSnapshots;

use App\Filament\Resources\CustomerSnapshots\Pages\ListCustomerSnapshots;
use App\Filament\Resources\CustomerSnapshots\Pages\ViewCustomerSnapshot;
use App\Filament\Resources\CustomerSnapshots\Schemas\CustomerSnapshotInfolist;
use App\Filament\Resources\CustomerSnapshots\Tables\CustomerSnapshotsTable;
use App\Models\CustomerSnapshot;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class CustomerSnapshotResource extends Resource
{
    protected static ?string $model = CustomerSnapshot::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?string $navigationLabel = 'Clientes';

    protected static ?string $modelLabel = 'cliente';

    protected static ?string $pluralModelLabel = 'clientes';

    protected static ?int $navigationSort = 30;

    public static function infolist(Schema $schema): Schema
    {
        return CustomerSnapshotInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CustomerSnapshotsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCustomerSnapshots::route('/'),
            'view' => ViewCustomerSnapshot::route('/{record}'),
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
