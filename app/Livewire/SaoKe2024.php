<?php

namespace App\Livewire;

use App\Models\Transaction;
use Exception;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;

class SaoKe2024 extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    /**
     * @throws Exception
     */
    public function table(Table $table): Table
    {
        return $table
            ->query(Transaction::query())
            ->columns([
                TextColumn::make('donated_at')
                    ->label('Ngày giao dịch')
                    ->dateTime('d/m/Y'),
                TextColumn::make('bank')
                    ->label('Ngân hàng')
                    ->formatStateUsing(function ($state) {
                        return match ($state) {
                            'vcb' => 'Vietcombank',
                            'icb' => 'Vietinbank',
                            default => $state,
                        };
                    }),
                TextColumn::make('code')
                    ->label('Mã giao dịch')
                    ->searchable(),
                TextColumn::make('amount')
                    ->label('Số tiền')
                    ->money('VND'),
                TextColumn::make('description')
                    ->label('Chi tiết giao dịch')
                    ->searchable(),
            ])
            ->filters([
                SelectFilter::make('bank')
                    ->options([
                        'vcb' => 'Vietcombank',
                        'icb' => 'Vietinbank',
                    ]),
                Tables\Filters\Filter::make('amount')
                    ->form([
                        Forms\Components\TextInput::make('amount_from')
                            ->label('Số tiền từ')
                            ->numeric(),
                        Forms\Components\TextInput::make('amount_to')
                            ->label('Đến số tiền')
                            ->numeric(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['amount_from'] ?? null,
                                fn (Builder $query, $amount): Builder => $query->where('amount', '>=', $amount),
                            )
                            ->when(
                                $data['amount_to'] ?? null,
                                fn (Builder $query, $amount): Builder => $query->where('amount', '<=', $amount),
                            );
                    }),
                Tables\Filters\Filter::make('donated_at')
                    ->form([
                        Forms\Components\DatePicker::make('donated_from')
                            ->label('Từ ngày'),
                        Forms\Components\DatePicker::make('donated_to')
                            ->label('Đến ngày'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['donated_from'] ?? null,
                                fn (Builder $query, $date): Builder => $query->whereDate('donated_at', '>=', $date),
                            )
                            ->when(
                                $data['donated_to'] ?? null,
                                fn (Builder $query, $date): Builder => $query->whereDate('donated_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                // ...
            ])
            ->bulkActions([
                // ...
            ])
            ->paginated([10, 20, 25, 50, 100])
            ->defaultPaginationPageOption(20)
            ->defaultSort('donated_at');
    }

    public function render()
    {
        return view('livewire.sao-ke2024');
    }
}
