<?php

namespace App\Filament\Resources\WebhookEvents\Pages;

use App\Filament\Resources\WebhookEvents\WebhookEventResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListWebhookEvents extends ListRecords
{
    protected static string $resource = WebhookEventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
