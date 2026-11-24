<?php

namespace App\Filament\Resources\NotificationLogs\Tables;

use App\Jobs\SendWhatsAppNotification;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class NotificationLogsTable
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
                TextColumn::make('channel')
                    ->label('Canal')
                    ->badge(),
                TextColumn::make('recipient')
                    ->label('Destinatario')
                    ->searchable()
                    ->limit(30),
                TextColumn::make('template')
                    ->label('Plantilla')
                    ->placeholder('-')
                    ->toggleable(),
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
                TextColumn::make('attempts')
                    ->label('Intentos')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('sent_at')
                    ->label('Enviado')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('Pendiente')
                    ->sortable(),
                TextColumn::make('last_error')
                    ->label('Último error')
                    ->limit(40)
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('channel')
                    ->label('Canal')
                    ->options([
                        'whatsapp' => 'WhatsApp',
                        'email' => 'Correo',
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
                    ->label('Reenviar')
                    ->icon('heroicon-o-arrow-path')
                    ->requiresConfirmation()
                    ->visible(fn (): bool => auth()->user()?->isAdmin() ?? false)
                    ->action(fn ($record) => SendWhatsAppNotification::dispatch($record->id)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
