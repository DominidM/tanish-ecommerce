<?php

namespace App\Filament\Resources\InventoryMovements\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class InventoryMovementInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('productSnapshot.name')
                    ->label('Product snapshot')
                    ->placeholder('-'),
                TextEntry::make('woo_product_id')
                    ->numeric(),
                TextEntry::make('movement_type'),
                TextEntry::make('quantity_change')
                    ->numeric(),
                TextEntry::make('stock_before')
                    ->numeric(),
                TextEntry::make('stock_after')
                    ->numeric(),
                TextEntry::make('reason')
                    ->columnSpanFull(),
                TextEntry::make('user.name')
                    ->label('User')
                    ->placeholder('-'),
                TextEntry::make('source'),
                TextEntry::make('reference')
                    ->placeholder('-'),
                TextEntry::make('metadata')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
