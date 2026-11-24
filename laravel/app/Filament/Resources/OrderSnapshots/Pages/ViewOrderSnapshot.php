<?php

namespace App\Filament\Resources\OrderSnapshots\Pages;

use App\Filament\Resources\OrderSnapshots\OrderSnapshotResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewOrderSnapshot extends ViewRecord
{
    protected static string $resource = OrderSnapshotResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
