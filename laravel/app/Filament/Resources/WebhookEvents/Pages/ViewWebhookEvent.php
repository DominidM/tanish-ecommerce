<?php

namespace App\Filament\Resources\WebhookEvents\Pages;

use App\Filament\Resources\WebhookEvents\WebhookEventResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewWebhookEvent extends ViewRecord
{
    protected static string $resource = WebhookEventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
