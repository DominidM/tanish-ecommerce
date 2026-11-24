<?php

namespace App\Filament\Resources\ProductSnapshots\Pages;

use App\Filament\Resources\ProductSnapshots\ProductSnapshotResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewProductSnapshot extends ViewRecord
{
    protected static string $resource = ProductSnapshotResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
