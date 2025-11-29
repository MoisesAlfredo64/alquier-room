<?php

namespace App\Livewire\Admin;

use App\Models\CashBox;
use App\Models\Expense;
use App\Models\Payment;
use App\Exports\CashMovementsExport;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class CashComponent extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';
    public $initial_amount, $description, $cashbox_id;
    public $isEditMode = false;
    public $searchTerm;
    public $cajaExiste;

    protected $listeners = ['delete'];

    protected $rules = [
        'initial_amount' => 'required'
    ];

    public function render()
    {
        $this->cajaExiste = CashBox::where('status', 1)->first();

        // Obtener movimientos de caja abierta si existe
        $movimientos = collect();
        $totalIngresos = 0;
        $totalEgresos = 0;

        if ($this->cajaExiste) {
            // Obtener pagos (ingresos)
            $ingresos = Payment::with('rent.client', 'rent.room')
                ->where('cashbox_id', $this->cajaExiste->id)
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

            // Obtener gastos (egresos)
            $egresos = Expense::where('cashbox_id', $this->cajaExiste->id)
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
            $movimientos = $ingresos->concat($egresos)->sortByDesc('fecha');
            $totalIngresos = $ingresos->sum('monto');
            $totalEgresos = $egresos->sum('monto');
        }

        $cashboxs = CashBox::where('initial_amount', 'like', '%' . $this->searchTerm . '%')
            ->orWhere('status', 'like', '%' . $this->searchTerm . '%')
            ->orderBy('id', 'desc')
            ->paginate(10);

        return view('livewire.admin.cash-component', [
            'cashboxs' => $cashboxs,
            'movimientos' => $movimientos,
            'totalIngresos' => $totalIngresos,
            'totalEgresos' => $totalEgresos
        ])
            ->extends('admin.layouts.app');
    }

    public function resetInputFields()
    {
        $this->initial_amount = '';
        $this->cashbox_id = '';
        $this->isEditMode = false;
    }

    public function storeOrUpdate()
    {
        $this->validate();

        CashBox::updateOrCreate(
            ['id' => $this->isEditMode ? $this->cashbox_id : null],
            [
                'initial_amount' => $this->initial_amount,
                'user_id' => auth()->user()->id
            ]
        );

        $message = $this->isEditMode ? 'Monto inicial actualizada exitosamente.' : 'Monto inicial creada con éxito.';
        session(null)->flash('message', $message);

        $this->resetInputFields();
        $this->dispatch('cashStoreOrUpdate');
    }

    public function edit($id)
    {
        $this->resetValidation();
        $type = CashBox::findOrFail($id);
        $this->cashbox_id = $id;
        $this->initial_amount = $type->initial_amount;
        $this->isEditMode = true;
    }

    public function delete($id)
    {
        $type = CashBox::find($id);
        if ($type) {
            $type->delete();
        } else {
            session(null)->flash('message', 'Monto inicial no encontrada.');
        }
    }

    public function cerrarCaja() {
        $gasto = Expense::where('cashbox_id', $this->cajaExiste->id)->sum('amount');
        $this->cajaExiste->status = 0;
        $this->cajaExiste->closing_date = date('Y-m-d H:i:s');
        $this->cajaExiste->spent = $gasto;
        $this->cajaExiste->update();
        session(null)->flash('message', 'Caja Cerrado.');
    }

    public function exportMovements()
    {
        if (!$this->cajaExiste) {
            session()->flash('error', 'No hay caja abierta para exportar.');
            return;
        }

        return Excel::download(
            new CashMovementsExport($this->cajaExiste->id), 
            'movimientos_caja_' . date('Y-m-d') . '.xlsx'
        );
    }
}
