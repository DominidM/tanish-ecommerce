<?php

namespace App\Filament\Resources\WebhookEvents\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class WebhookEventInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('delivery_id')
                    ->placeholder('-'),
                TextEntry::make('payload_hash'),
                TextEntry::make('topic'),
                TextEntry::make('resource')
                    ->placeholder('-'),
                TextEntry::make('event_action')
                    ->placeholder('-'),
                TextEntry::make('source')
                    ->placeholder('-'),
                TextEntry::make('payload')
                    ->columnSpanFull(),
                TextEntry::make('status'),
                TextEntry::make('attempts')
                    ->numeric(),
                TextEntry::make('last_error')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('source_updated_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('received_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('processed_at')
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
