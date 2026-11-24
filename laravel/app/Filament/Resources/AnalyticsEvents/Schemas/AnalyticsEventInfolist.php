<?php

namespace App\Filament\Resources\AnalyticsEvents\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class AnalyticsEventInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name'),
                TextEntry::make('event_id')
                    ->placeholder('-'),
                TextEntry::make('woo_order_id')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('payload')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('status'),
                TextEntry::make('response')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('sent_at')
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
