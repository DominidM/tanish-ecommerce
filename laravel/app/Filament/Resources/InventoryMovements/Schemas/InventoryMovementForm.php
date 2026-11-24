<?php

namespace App\Filament\Resources\InventoryMovements\Schemas;

use App\Models\ProductSnapshot;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class InventoryMovementForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('woo_product_id')
                    ->label('Producto')
                    ->options(fn (): array => ProductSnapshot::query()
                        ->where('manage_stock', true)
                        ->orderBy('name')
                        ->get()
                        ->mapWithKeys(fn (ProductSnapshot $product): array => [
                            $product->woo_id => trim($product->name.($product->sku ? " ({$product->sku})" : '')),
                        ])
                        ->all())
                    ->searchable()
                    ->required(),
                Select::make('movement_type')
                    ->label('Tipo de movimiento')
                    ->options([
                        'entry' => 'Entrada',
                        'exit' => 'Salida',
                        'adjustment' => 'Ajuste',
                    ])
                    ->required(),
                TextInput::make('quantity')
                    ->label('Cantidad')
                    ->numeric()
                    ->required()
                    ->minValue(0)
                    ->step(0.01)
                    ->helperText('En "Ajuste" indica el stock final. En entrada/salida, la cantidad a mover.'),
                Textarea::make('reason')
                    ->label('Motivo')
                    ->required()
                    ->maxLength(500)
                    ->columnSpanFull(),
            ]);
    }
}
