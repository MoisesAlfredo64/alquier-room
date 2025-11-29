<?php

namespace App\Exports;

use App\Models\ElectricityReading;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ElectricityReadingExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $readingId;

    public function __construct($readingId)
    {
        $this->readingId = $readingId;
    }

    public function collection()
    {
        return ElectricityReading::with(['client', 'rent.room'])
            ->where('id', $this->readingId)
            ->get();
    }

    public function headings(): array
    {
        return [
            'Fecha de Lectura',
            'Cliente',
            'Número de Identificación',
            'Habitación',
            'Lectura Inicial (KWH)',
            'Lectura Final (KWH)',
            'Consumo (KWH)',
            'Precio KWH',
            'Importe Total',
        ];
    }

    public function map($reading): array
    {
        return [
            $reading->reading_date->format('d/m/Y'),
            $reading->client->full_name,
            $reading->client->identification_number,
            'Habitación N° ' . $reading->rent->room->number,
            number_format($reading->initial_reading, 2),
            number_format($reading->final_reading, 2),
            number_format($reading->consumption, 2),
            number_format($reading->kwh_price, 2),
            number_format($reading->total_amount, 2),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
