<?php

namespace App\Filament\Resources\ProductSnapshots\Pages;

use App\Filament\Resources\ProductSnapshots\ProductSnapshotResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProductSnapshots extends ListRecords
{
    protected static string $resource = ProductSnapshotResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
