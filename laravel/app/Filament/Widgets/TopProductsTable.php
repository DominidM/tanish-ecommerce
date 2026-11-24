<?php

namespace App\Filament\Widgets;

use App\Models\InventoryMovement;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class TopProductsTable extends TableWidget
{
    protected static ?string $heading = 'Productos más vendidos';

    protected static ?int $sort = 5;

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => InventoryMovement::query()
                ->join('product_snapshots', 'product_snapshots.woo_id', '=', 'inventory_movements.woo_product_id')
                ->where('inventory_movements.movement_type', InventoryMovement::TYPE_EXIT)
                ->selectRaw('product_snapshots.name as product_name, inventory_movements.woo_product_id as woo_product_id, SUM(ABS(inventory_movements.quantity_change)) as sold_quantity')
                ->groupBy('product_snapshots.name', 'inventory_movements.woo_product_id')
                ->orderByDesc('sold_quantity'))
            ->columns([
                TextColumn::make('product_name')
                    ->label('Producto'),
                TextColumn::make('woo_product_id')
                    ->label('# Woo')
                    ->numeric(),
                TextColumn::make('sold_quantity')
                    ->label('Unidades vendidas')
                    ->numeric()
                    ->sortable(),
            ])
            ->paginated(false)
            ->defaultSort('sold_quantity', 'desc');
    }
}
