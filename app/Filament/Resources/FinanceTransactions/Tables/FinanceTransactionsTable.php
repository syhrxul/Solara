<?php

namespace App\Filament\Resources\FinanceTransactions\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class FinanceTransactionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('transaction_date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('title')
                    ->label('Keterangan')
                    ->searchable()
                    ->weight('medium')
                    ->description(fn ($record) => $record->category?->name),

                BadgeColumn::make('type')
                    ->label('Jenis')
                    ->colors([
                        'success' => 'income',
                        'danger'  => 'expense',
                    ])
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'income'  => '📈 Pemasukan',
                        'expense' => '📉 Pengeluaran',
                        default   => $state,
                    }),

                TextColumn::make('amount')
                    ->label('Jumlah')
                    ->money('IDR', locale: 'id')
                    ->sortable()
                    ->color(fn ($record) => $record->type === 'income' ? 'success' : 'danger')
                    ->weight('bold'),

                TextColumn::make('payment_method')
                    ->label('Metode')
                    ->formatStateUsing(fn (?string $state) => match ($state) {
                        'cash'     => '💵 Tunai',
                        'transfer' => '🏦 Transfer',
                        'card'     => '💳 Kartu',
                        'e-wallet' => '📱 E-Wallet',
                        default    => $state ?? '-',
                    })
                    ->toggleable(),
            ])
            ->defaultSort('transaction_date', 'desc')
            ->filters([
                SelectFilter::make('type')
                    ->label('Jenis')
                    ->options([
                        'income'  => 'Pemasukan',
                        'expense' => 'Pengeluaran',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
