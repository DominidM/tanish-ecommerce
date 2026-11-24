<?php

namespace App\Filament\Resources\OrderSnapshots\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class OrderSnapshotsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('date_created', 'desc')
            ->columns([
                TextColumn::make('woo_id')
                    ->label('# Woo')
                    ->sortable(),
                TextColumn::make('number')
                    ->label('Número')
                    ->searchable(),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'pending' => 'Pendiente',
                        'processing' => 'En proceso',
                        'completed' => 'Completado',
                        'cancelled' => 'Cancelado',
                        'refunded' => 'Reembolsado',
                        'failed' => 'Fallido',
                        'on-hold' => 'En espera',
                        default => $state ?? '-',
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        'completed' => 'success',
                        'processing' => 'info',
                        'cancelled', 'failed' => 'danger',
                        'refunded', 'on-hold' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('total')
                    ->label('Total')
                    ->money('PEN')
                    ->sortable(),
                TextColumn::make('customer_name')
                    ->label('Cliente')
                    ->searchable()
                    ->placeholder('-'),
                TextColumn::make('customer_email')
                    ->label('Correo')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('payment_method_title')
                    ->label('Pago')
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('date_created')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('stock_recorded_at')
                    ->label('Stock registrado')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('Pendiente')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'pending' => 'Pendiente',
                        'processing' => 'En proceso',
                        'completed' => 'Completado',
                        'on-hold' => 'En espera',
                        'cancelled' => 'Cancelado',
                        'refunded' => 'Reembolsado',
                        'failed' => 'Fallido',
                    ]),
            ])
            ->recordActions([
                ViewAction::make()->label('Ver'),
            ]);
    }
}
