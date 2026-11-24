<?php

namespace App\Filament\Resources\InventoryMovements\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class InventoryMovementsTable
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
                TextColumn::make('productSnapshot.name')
                    ->label('Producto')
                    ->searchable()
                    ->placeholder('Producto no sincronizado'),
                TextColumn::make('movement_type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'entry' => 'Entrada',
                        'exit' => 'Salida',
                        'adjustment' => 'Ajuste',
                        default => $state ?? '-',
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        'entry' => 'success',
                        'exit' => 'danger',
                        'adjustment' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('quantity_change')
                    ->label('Cambio')
                    ->numeric()
                    ->color(fn ($state): string => (float) $state < 0 ? 'danger' : 'success'),
                TextColumn::make('stock_before')
                    ->label('Antes')
                    ->numeric(),
                TextColumn::make('stock_after')
                    ->label('Después')
                    ->numeric(),
                TextColumn::make('user.name')
                    ->label('Usuario')
                    ->placeholder('Sistema'),
                TextColumn::make('source')
                    ->label('Origen')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'manual' => 'Manual',
                        'order' => 'Venta',
                        'reversal' => 'Reversión',
                        'reconciliation' => 'Conciliación',
                        default => $state ?? '-',
                    }),
                TextColumn::make('reason')
                    ->label('Motivo')
                    ->limit(40)
                    ->tooltip(fn ($record): ?string => $record->reason)
                    ->toggleable(),
                TextColumn::make('reference')
                    ->label('Referencia')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('woo_product_id')
                    ->label('Woo ID')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('movement_type')
                    ->label('Tipo')
                    ->options([
                        'entry' => 'Entrada',
                        'exit' => 'Salida',
                        'adjustment' => 'Ajuste',
                    ]),
                SelectFilter::make('source')
                    ->label('Origen')
                    ->options([
                        'manual' => 'Manual',
                        'order' => 'Venta',
                        'reversal' => 'Reversión',
                        'reconciliation' => 'Conciliación',
                    ]),
            ])
            ->recordActions([
                ViewAction::make()->label('Ver'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
