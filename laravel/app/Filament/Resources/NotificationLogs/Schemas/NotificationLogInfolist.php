<?php

namespace App\Filament\Resources\NotificationLogs\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class NotificationLogInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('channel'),
                TextEntry::make('recipient'),
                TextEntry::make('template')
                    ->placeholder('-'),
                TextEntry::make('payload')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('status'),
                TextEntry::make('attempts')
                    ->numeric(),
                TextEntry::make('last_error')
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
