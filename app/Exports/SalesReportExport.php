<?php

namespace App\Exports;

use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class SalesReportExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(private Builder $query)
    {
        //
    }

    public function collection()
    {
        return $this->query->with(['router', 'profile'])->orderByDesc('created_at')->get();
    }

    public function headings(): array
    {
        return ['Date', 'Routeur', 'Profil', 'Montant', 'Client'];
    }

    public function map($transaction): array
    {
        return [
            $transaction->created_at?->format('d/m/Y H:i'),
            $transaction->router?->name ?? 'Non assigné',
            $transaction->profile?->name ?? 'Profil inconnu',
            number_format($transaction->total_price, 0, ',', ' ') . ' FCFA',
            $transaction->customer_number,
        ];
    }
}
