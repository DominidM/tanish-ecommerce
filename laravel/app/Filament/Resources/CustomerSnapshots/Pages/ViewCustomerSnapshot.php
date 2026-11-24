<?php

namespace App\Filament\Resources\CustomerSnapshots\Pages;

use App\Filament\Resources\CustomerSnapshots\CustomerSnapshotResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCustomerSnapshot extends ViewRecord
{
    protected static string $resource = CustomerSnapshotResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
