<?php

namespace App\Filament\Resources\CustomerSnapshots\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CustomerSnapshotsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('email')
            ->columns([
                TextColumn::make('woo_id')
                    ->label('# Woo')
                    ->sortable(),
                TextColumn::make('first_name')
                    ->label('Nombre')
                    ->searchable(),
                TextColumn::make('last_name')
                    ->label('Apellido')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Correo')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('role')
                    ->label('Rol')
                    ->badge()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Sincronizado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->recordActions([
                ViewAction::make()->label('Ver'),
            ]);
    }
}
