<?php

namespace App\Exports;

// use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Support\Collection;

class SalesReportExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(private Collection $rows)
    {
        //
    }

    public function collection(): Collection
    {
        return $this->rows->sortByDesc('date')->values();
    }

    public function headings(): array
    {
        return ['Date', 'Routeur', 'Profil', 'Montant', 'Client', 'Source'];
    }

    public function map($row): array
    {
        return [
            $row['date']?->format('d/m/Y H:i'),
            $row['router_label'] ?? 'Non assigné',
            $row['profile_label'] ?? 'Profil inconnu',
            number_format((float) ($row['amount'] ?? 0), 0, ',', ' ') . ' FCFA',
            $row['customer'] ?? '-',
            $row['source'] ?? '-',
        ];
    }
}
