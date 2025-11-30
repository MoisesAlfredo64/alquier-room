<?php

namespace App\Exports;

use App\Models\Expense;
use App\Models\Payment;
use App\Models\Income;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MonthlyCashMovementsExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected string $month; // Format YYYY-MM

    public function __construct(string $month)
    {
        $this->month = $month; // e.g. 2025-11
    }

    public function collection()
    {
        // Parse month boundaries
        [$year, $mon] = explode('-', $this->month);
        $start = Carbon::createFromDate((int)$year, (int)$mon, 1)->startOfDay();
        $end = (clone $start)->endOfMonth();

        $ingresos = Payment::with('rent.client', 'rent.room')
            ->whereBetween('created_at', [$start, $end])
            ->get()
            ->map(function ($payment) {
                return [
                    'tipo' => 'Ingreso',
                    'descripcion' => 'Pago alquiler - ' . ($payment->rent->client->full_name ?? 'N/A') . ' (Habitación ' . ($payment->rent->room->number ?? 'N/A') . ')',
                    'monto' => $payment->amount,
                    'fecha' => $payment->created_at,
                ];
            });

        $ingresosExtras = Income::whereBetween('created_at', [$start, $end])
            ->get()
            ->map(function ($income) {
                return [
                    'tipo' => 'Ingreso',
                    'descripcion' => 'Ingreso extra - ' . $income->description,
                    'monto' => $income->amount,
                    'fecha' => $income->created_at,
                ];
            });

        $egresos = Expense::whereBetween('created_at', [$start, $end])
            ->get()
            ->map(function ($expense) {
                return [
                    'tipo' => 'Egreso',
                    'descripcion' => $expense->description,
                    'monto' => $expense->amount,
                    'fecha' => $expense->created_at,
                ];
            });

        return $ingresos->concat($ingresosExtras)->concat($egresos)->sortBy('fecha')->values();
    }

    public function headings(): array
    {
        return ['Tipo', 'Descripción', 'Monto', 'Fecha/Hora'];
    }

    public function map($row): array
    {
        return [
            $row['tipo'],
            $row['descripcion'],
            number_format($row['monto'], 2),
            Carbon::parse($row['fecha'])->format('d/m/Y H:i'),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [1 => ['font' => ['bold' => true]]];
    }
}
