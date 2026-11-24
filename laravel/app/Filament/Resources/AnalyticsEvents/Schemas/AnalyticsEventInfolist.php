<?php

namespace App\Filament\Resources\AnalyticsEvents\Schemas;

use App\Filament\Support\JsonEntry;
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
                JsonEntry::make('payload'),
                TextEntry::make('status'),
                JsonEntry::make('response', 'Respuesta GA4'),
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
