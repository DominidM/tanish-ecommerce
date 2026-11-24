<?php

namespace App\Filament\Resources\AnalyticsEvents\Tables;

use App\Jobs\SendPurchaseToGa4;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AnalyticsEventsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Evento')
                    ->badge()
                    ->searchable(),
                TextColumn::make('woo_order_id')
                    ->label('Pedido')
                    ->numeric()
                    ->sortable()
                    ->placeholder('-'),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'sent' => 'success',
                        'pending' => 'warning',
                        'failed' => 'danger',
                        'skipped' => 'gray',
                        default => 'gray',
                    }),
                TextColumn::make('sent_at')
                    ->label('Enviado')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('Pendiente')
                    ->sortable(),
                TextColumn::make('event_id')
                    ->label('Event ID')
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('name')
                    ->label('Evento')
                    ->options([
                        'purchase' => 'purchase',
                        'view_item' => 'view_item',
                        'add_to_cart' => 'add_to_cart',
                        'begin_checkout' => 'begin_checkout',
                    ]),
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'pending' => 'Pendiente',
                        'sent' => 'Enviado',
                        'failed' => 'Fallido',
                        'skipped' => 'Omitido',
                    ]),
            ])
            ->recordActions([
                ViewAction::make()->label('Ver'),
                Action::make('resend')
                    ->label('Reenviar a GA4')
                    ->icon('heroicon-o-arrow-path')
                    ->requiresConfirmation()
                    ->visible(fn (): bool => auth()->user()?->isAdmin() ?? false)
                    ->action(fn ($record) => SendPurchaseToGa4::dispatch($record->id)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
