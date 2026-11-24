<?php

namespace App\Filament\Resources\NotificationLogs\Pages;

use App\Filament\Resources\NotificationLogs\NotificationLogResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewNotificationLog extends ViewRecord
{
    protected static string $resource = NotificationLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
