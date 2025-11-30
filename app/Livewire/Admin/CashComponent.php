<?php

namespace App\Livewire\Admin;

use App\Models\CashBox;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\Income;
use App\Exports\CashMovementsExport;
use App\Exports\MonthlyCashMovementsExport;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class CashComponent extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';

    public function exportAllMovements()
    {
        return Excel::download(
            new CashMovementsExport(null, true),
            'movimientos_caja_general_' . date('Y-m-d') . '.xlsx'
        );
    }
    public $initial_amount, $description, $cashbox_id;
    public $isEditMode = false;
    public $searchTerm;
    public $cajaExiste;

    protected $listeners = ['delete'];

    protected $rules = [
    ];

    public function render()
    {
        $this->cajaExiste = CashBox::where('status', 1)->first();

        // Obtener movimientos de caja abierta si existe
        $movimientos = collect();
        $totalIngresos = 0;
        $totalEgresos = 0;

        if ($this->cajaExiste) {
            // Obtener pagos (ingresos por alquiler)
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

            // Obtener ingresos extra (manuales)
            $ingresosExtras = Income::where('cashbox_id', $this->cajaExiste->id)
                ->get()
                ->map(function($income) {
                    return [
                        'tipo' => 'Ingreso',
                        'descripcion' => 'Ingreso extra - ' . $income->description,
                        'monto' => $income->amount,
                        'fecha' => $income->created_at
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
            $movimientos = $ingresos->concat($ingresosExtras)->concat($egresos)->sortByDesc('fecha');
            $totalIngresos = $ingresos->sum('monto') + $ingresosExtras->sum('monto');
            $totalEgresos = $egresos->sum('monto');
        }

        $query = CashBox::withSum('payments', 'amount')
            ->withSum('expenses', 'amount')
            ->withSum('incomes', 'amount')
            ->orderBy('id', 'desc');

        if (!empty($this->searchTerm)) {
            $query->where(function ($q) {
                $q->where('status', 'like', '%' . $this->searchTerm . '%')
                  ->orWhereDate('created_at', $this->searchTerm);
            });
        }

        $cashboxs = $query->get();

        // Agrupar por mes y calcular totales
        $totalesPorMes = [];
        $ingresoGeneral = 0;
        $egresoGeneral = 0;
        foreach ($cashboxs as $caja) {
            $mes = \Carbon\Carbon::parse($caja->created_at)->format('Y-m');
            if (!isset($totalesPorMes[$mes])) {
                $totalesPorMes[$mes] = ['ingreso' => 0, 'egreso' => 0];
            }
            $totalesPorMes[$mes]['ingreso'] += ($caja->payments_sum_amount ?? 0) + ($caja->incomes_sum_amount ?? 0);
            $totalesPorMes[$mes]['egreso'] += $caja->expenses_sum_amount ?? 0;
            $ingresoGeneral += ($caja->payments_sum_amount ?? 0) + ($caja->incomes_sum_amount ?? 0);
            $egresoGeneral += $caja->expenses_sum_amount ?? 0;
        }

        // Paginación manual para la tabla
        $page = request()->get('page', 1);
        $perPage = 10;
        $paginated = $cashboxs->slice(($page - 1) * $perPage, $perPage)->values();
        $paginator = new \Illuminate\Pagination\LengthAwarePaginator($paginated, $cashboxs->count(), $perPage, $page);

        return view('livewire.admin.cash-component', [
            'cashboxs' => $paginator,
            'movimientos' => $movimientos,
            'totalIngresos' => $totalIngresos,
            'totalEgresos' => $totalEgresos,
            'totalesPorMes' => $totalesPorMes,
            'ingresoGeneral' => $ingresoGeneral,
            'egresoGeneral' => $egresoGeneral
        ]);
    }

    public function resetInputFields()
    {
        $this->initial_amount = '';
        $this->cashbox_id = '';
        $this->isEditMode = false;
    }

    public function abrirCaja()
    {
        $existe = CashBox::where('status', 1)->first();
        if ($existe) {
            session()->flash('error', 'Ya existe una caja abierta.');
            return;
        }

        CashBox::create([
            'status' => 1,
            'user_id' => Auth::id(),
        ]);

        session()->flash('message', 'Caja abierta correctamente.');
    }

    // Edición del monto inicial deshabilitada por requerimiento

    public function delete($id)
    {
        $type = CashBox::find($id);
        if ($type) {
            $type->delete();
        } else {
            session(null)->flash('message', 'Caja no encontrada.');
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

    public function exportMovements($cashboxId = null)
    {
        $id = $cashboxId ?? ($this->cajaExiste ? $this->cajaExiste->id : null);
        if (!$id) {
            session()->flash('error', 'No hay caja seleccionada para exportar.');
            return;
        }
        return Excel::download(
            new CashMovementsExport($id), 
            'movimientos_caja_' . $id . '_' . date('Y-m-d') . '.xlsx'
        );
    }

    public function exportMonth($month)
    {
        // Validar formato YYYY-MM
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            session()->flash('error', 'Formato de mes inválido.');
            return;
        }
        return Excel::download(
            new MonthlyCashMovementsExport($month),
            'movimientos_mes_' . $month . '.xlsx'
        );
    }
}
