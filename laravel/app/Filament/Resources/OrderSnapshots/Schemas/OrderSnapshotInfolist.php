<?php

namespace App\Filament\Resources\OrderSnapshots\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class OrderSnapshotInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('woo_id')
                    ->numeric(),
                TextEntry::make('number')
                    ->placeholder('-'),
                TextEntry::make('status')
                    ->placeholder('-'),
                TextEntry::make('currency')
                    ->placeholder('-'),
                TextEntry::make('total')
                    ->numeric(),
                TextEntry::make('customer_id')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('customer_name')
                    ->placeholder('-'),
                TextEntry::make('customer_email')
                    ->placeholder('-'),
                TextEntry::make('payment_method')
                    ->placeholder('-'),
                TextEntry::make('payment_method_title')
                    ->placeholder('-'),
                TextEntry::make('date_created')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('date_paid')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('line_items')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('payload')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('stock_recorded_at')
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
