<?php

namespace App\Filament\Resources\AnalyticsEvents\Pages;

use App\Filament\Resources\AnalyticsEvents\AnalyticsEventResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewAnalyticsEvent extends ViewRecord
{
    protected static string $resource = AnalyticsEventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
