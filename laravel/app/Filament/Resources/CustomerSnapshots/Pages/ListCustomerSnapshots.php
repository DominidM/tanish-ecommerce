<?php

namespace App\Filament\Resources\CustomerSnapshots\Pages;

use App\Filament\Resources\CustomerSnapshots\CustomerSnapshotResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCustomerSnapshots extends ListRecords
{
    protected static string $resource = CustomerSnapshotResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
