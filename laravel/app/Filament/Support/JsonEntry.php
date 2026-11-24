<?php

namespace App\Filament\Support;

use Filament\Infolists\Components\TextEntry;

class JsonEntry
{
    public static function make(string $name, ?string $label = null): TextEntry
    {
        return TextEntry::make($name)
            ->label($label ?? str($name)->headline())
            ->placeholder('-')
            ->formatStateUsing(static function (mixed $state): string {
                if ($state === null || $state === '') {
                    return '-';
                }

                if (is_string($state)) {
                    $decoded = json_decode($state, true);

                    if (json_last_error() === JSON_ERROR_NONE) {
                        $state = $decoded;
                    }
                }

                if (is_array($state) || is_object($state)) {
                    $json = json_encode(
                        $state,
                        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                    );

                    return $json !== false ? $json : '-';
                }

                return (string) $state;
            })
            ->extraAttributes([
                'class' => 'tanish-json',
                'style' => 'white-space: pre-wrap; word-break: break-word; overflow-wrap: anywhere; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 12px; line-height: 1.6; max-height: 28rem; overflow: auto;',
            ])
            ->columnSpanFull();
    }
}
