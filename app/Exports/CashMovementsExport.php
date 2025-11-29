<?php

namespace App\Exports;

use App\Models\CashBox;
use App\Models\Expense;
use App\Models\Payment;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CashMovementsExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $cashboxId;

    public function __construct($cashboxId)
    {
        $this->cashboxId = $cashboxId;
    }

    public function collection()
    {
        $movimientos = collect();

        // Obtener ingresos (pagos)
        $ingresos = Payment::with('rent.client', 'rent.room')
            ->where('cashbox_id', $this->cashboxId)
            ->get()
            ->map(function($payment) {
                return [
                    'tipo' => 'Ingreso',
                    'descripcion' => 'Pago alquiler - ' . 
                        ($payment->rent->client->full_name ?? 'N/A') . 
                        ' (Habitación ' . ($payment->rent->room->number ?? 'N/A') . ')',
                    'monto' => $payment->amount,
                    'fecha' => $payment->created_at
                ];
            });

        // Obtener egresos (gastos)
        $egresos = Expense::where('cashbox_id', $this->cashboxId)
            ->get()
            ->map(function($expense) {
                return [
                    'tipo' => 'Egreso',
                    'descripcion' => $expense->description,
                    'monto' => $expense->amount,
                    'fecha' => $expense->created_at
                ];
            });

        // Combinar y ordenar
        return $ingresos->concat($egresos)->sortBy('fecha');
    }

    public function headings(): array
    {
        return [
            'Tipo',
            'Descripción',
            'Monto',
            'Fecha/Hora'
        ];
    }

    public function map($movimiento): array
    {
        return [
            $movimiento['tipo'],
            $movimiento['descripcion'],
            number_format($movimiento['monto'], 2),
            Carbon::parse($movimiento['fecha'])->format('d/m/Y H:i')
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]]
        ];
    }
}
