<?php

namespace App\Filament\Resources\InventoryMovements\Pages;

use App\Filament\Resources\InventoryMovements\InventoryMovementResource;
use App\Services\Inventory\InventoryService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateInventoryMovement extends CreateRecord
{
    protected static string $resource = InventoryMovementResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(InventoryService::class)->apply(
            wooProductId: (int) $data['woo_product_id'],
            type: (string) $data['movement_type'],
            quantity: (float) $data['quantity'],
            reason: (string) $data['reason'],
            userId: auth()->id(),
        );
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
