<?php

namespace App\Filament\Resources\ProductSnapshots\Tables;

use App\Models\ProductSnapshot;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ProductSnapshotsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                ImageColumn::make('image_url')
                    ->label('')
                    ->height(40)
                    ->width(40)
                    ->square(),
                TextColumn::make('name')
                    ->label('Producto')
                    ->searchable()
                    ->sortable()
                    ->description(fn (ProductSnapshot $record): ?string => filled($record->sku) ? "SKU: {$record->sku}" : null),
                TextColumn::make('price')
                    ->label('Precio')
                    ->money('PEN')
                    ->sortable(),
                TextColumn::make('stock_quantity')
                    ->label('Stock')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color(fn (ProductSnapshot $record): string => match (true) {
                        (float) $record->stock_quantity <= 0 => 'danger',
                        $record->isLowStock() => 'warning',
                        default => 'success',
                    }),
                TextColumn::make('stock_status')
                    ->label('Estado de stock')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'instock' => 'En stock',
                        'outofstock' => 'Sin stock',
                        'onbackorder' => 'En espera',
                        default => $state ?? '-',
                    }),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->toggleable(),
                TextColumn::make('woo_id')
                    ->label('Woo ID')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('woo_updated_at')
                    ->label('Actualizado en Woo')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('stock_status')
                    ->label('Estado de stock')
                    ->options([
                        'instock' => 'En stock',
                        'outofstock' => 'Sin stock',
                        'onbackorder' => 'En espera',
                    ]),
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'publish' => 'Publicado',
                        'draft' => 'Borrador',
                        'private' => 'Privado',
                        'pending' => 'Pendiente',
                    ]),
            ])
            ->recordActions([
                ViewAction::make()->label('Ver'),
            ]);
    }
}
