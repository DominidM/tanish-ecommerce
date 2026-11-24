<?php

namespace App\Filament\Resources\AnalyticsEvents\Pages;

use App\Filament\Resources\AnalyticsEvents\AnalyticsEventResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAnalyticsEvents extends ListRecords
{
    protected static string $resource = AnalyticsEventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
