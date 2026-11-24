<?php

namespace App\Filament\Resources\ProductSnapshots\Schemas;

use App\Filament\Support\JsonEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ProductSnapshotInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('woo_id')
                    ->numeric(),
                TextEntry::make('sku')
                    ->label('SKU')
                    ->placeholder('-'),
                TextEntry::make('name'),
                TextEntry::make('slug')
                    ->placeholder('-'),
                TextEntry::make('type')
                    ->placeholder('-'),
                TextEntry::make('status')
                    ->placeholder('-'),
                TextEntry::make('price')
                    ->money('PEN')
                    ->placeholder('-'),
                TextEntry::make('regular_price')
                    ->money('PEN')
                    ->placeholder('-'),
                TextEntry::make('sale_price')
                    ->money('PEN')
                    ->placeholder('-'),
                IconEntry::make('manage_stock')
                    ->boolean(),
                TextEntry::make('stock_quantity')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('stock_status')
                    ->placeholder('-'),
                JsonEntry::make('category_ids', 'Categorías'),
                ImageEntry::make('image_url')
                    ->placeholder('-'),
                JsonEntry::make('payload'),
                TextEntry::make('woo_created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('woo_updated_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
