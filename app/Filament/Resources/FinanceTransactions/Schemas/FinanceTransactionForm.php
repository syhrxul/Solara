<?php

namespace App\Filament\Resources\FinanceTransactions\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FinanceTransactionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Detail Transaksi')
                ->icon('heroicon-o-banknotes')
                ->schema([
                    TextInput::make('title')
                        ->label('Keterangan')
                        ->placeholder('Contoh: Gaji bulanan, Makan siang, Belanja...')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),

                    Grid::make(2)->schema([
                        Select::make('type')
                            ->label('Jenis')
                            ->options([
                                'income'  => '📈 Pemasukan',
                                'expense' => '📉 Pengeluaran',
                            ])
                            ->default('expense')
                            ->required()
                            ->live(),

                        TextInput::make('amount')
                            ->label('Jumlah (Rp)')
                            ->numeric()
                            ->prefix('Rp')
                            ->placeholder('0')
                            ->required()
                            ->minValue(0),
                    ]),

                    Grid::make(2)->schema([
                        Select::make('category_id')
                            ->label('Kategori')
                            ->relationship(
                                'category',
                                'name',
                                fn ($query) => $query->where('type', 'finance')
                                    ->orWhere('type', 'general')
                            )
                            ->searchable()
                            ->preload()
                            ->nullable(),

                        Select::make('payment_method')
                            ->label('Metode Pembayaran')
                            ->options([
                                'cash'      => '💵 Tunai',
                                'transfer'  => '🏦 Transfer Bank',
                                'card'      => '💳 Kartu',
                                'e-wallet'  => '📱 E-Wallet',
                            ])
                            ->nullable(),
                    ]),

                    DatePicker::make('transaction_date')
                        ->label('Tanggal Transaksi')
                        ->default(today())
                        ->native(false)
                        ->required(),

                    Textarea::make('description')
                        ->label('Catatan')
                        ->placeholder('Catatan tambahan...')
                        ->rows(2),
                ]),

            Section::make('Transaksi Berulang')
                ->icon('heroicon-o-arrow-path')
                ->schema([
                    Toggle::make('is_recurring')
                        ->label('Transaksi Berulang')
                        ->helperText('Contoh: bayar langganan, cicilan')
                        ->live(),

                    Select::make('recurring_period')
                        ->label('Periode')
                        ->options([
                            'daily'   => 'Harian',
                            'weekly'  => 'Mingguan',
                            'monthly' => 'Bulanan',
                            'yearly'  => 'Tahunan',
                        ])
                        ->visible(fn ($get) => $get('is_recurring')),
                ]),
        ]);
    }
}
