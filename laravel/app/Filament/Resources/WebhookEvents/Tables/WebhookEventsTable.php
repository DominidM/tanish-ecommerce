<?php

namespace App\Filament\Resources\WebhookEvents\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class WebhookEventsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('received_at', 'desc')
            ->columns([
                TextColumn::make('received_at')
                    ->label('Recibido')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),
                TextColumn::make('topic')
                    ->label('Tópico')
                    ->searchable()
                    ->badge(),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'processed' => 'success',
                        'failed' => 'danger',
                        'processing' => 'info',
                        'received' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('attempts')
                    ->label('Intentos')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('delivery_id')
                    ->label('Delivery ID')
                    ->limit(16)
                    ->tooltip(fn ($record): ?string => $record->delivery_id)
                    ->toggleable(),
                TextColumn::make('processed_at')
                    ->label('Procesado')
                    ->dateTime('d/m/Y H:i:s')
                    ->placeholder('Pendiente')
                    ->toggleable(),
                TextColumn::make('payload_hash')
                    ->label('Hash')
                    ->limit(16)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('last_error')
                    ->label('Último error')
                    ->limit(40)
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'received' => 'Recibido',
                        'processing' => 'Procesando',
                        'processed' => 'Procesado',
                        'failed' => 'Fallido',
                    ]),
                SelectFilter::make('topic')
                    ->label('Tópico')
                    ->options([
                        'order.created' => 'order.created',
                        'order.updated' => 'order.updated',
                        'order.deleted' => 'order.deleted',
                        'product.created' => 'product.created',
                        'product.updated' => 'product.updated',
                        'product.deleted' => 'product.deleted',
                        'customer.created' => 'customer.created',
                        'customer.updated' => 'customer.updated',
                        'customer.deleted' => 'customer.deleted',
                    ]),
            ])
            ->recordActions([
                ViewAction::make()->label('Ver'),
            ]);
    }
}
