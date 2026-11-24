<?php

namespace App\Filament\Resources\OrderSnapshots\Pages;

use App\Filament\Resources\OrderSnapshots\OrderSnapshotResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListOrderSnapshots extends ListRecords
{
    protected static string $resource = OrderSnapshotResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
