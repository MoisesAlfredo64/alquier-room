<?php

namespace App\Livewire\Admin;

use App\Models\CashBox;
use App\Models\Income;
use App\Exports\IncomesExport;
use Maatwebsite\Excel\Facades\Excel;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class IncomeComponent extends Component
{
    use WithPagination, WithFileUploads;
    protected $paginationTheme = 'bootstrap';

    public $amount, $description, $income_id, $photo;
    public $isEditMode = false;
    public $searchTerm, $fromDate, $toDate;

    protected $listeners = ['delete'];

    protected $rules = [
        'amount' => 'required|numeric',
        'description' => 'required|string|max:255',
        'photo' => 'nullable|image|mimes:jpg,png,jpeg|max:2048',
    ];

    public function render()
    {
        $query = Income::query();

        if (!empty($this->searchTerm)) {
            $query->where(function ($q) {
                $q->where('amount', 'like', '%' . $this->searchTerm . '%')
                    ->orWhere('description', 'like', '%' . $this->searchTerm . '%');
            });
        }

        if (!empty($this->fromDate) && !empty($this->toDate)) {
            $from = Carbon::parse($this->fromDate)->startOfDay();
            $to = Carbon::parse($this->toDate)->endOfDay();
            $query->whereBetween('created_at', [$from, $to]);
        }

        $incomes = $query->orderBy('id', 'desc')->paginate(10);

        return view('livewire.admin.income-component', ['incomes' => $incomes])
            ->extends('admin.layouts.app');
    }

    public function resetInputFields()
    {
        $this->amount = '';
        $this->description = '';
        $this->income_id = '';
        $this->photo = null;
        $this->isEditMode = false;
    }

    public function storeOrUpdate()
    {
        $this->validate();

        $caja = CashBox::where('status', 1)->first();
        if (!$caja) {
            session()->flash('warning', 'La caja está cerrada. Debe abrir caja para registrar ingresos.');
            return;
        }

        $data = [
            'amount' => $this->amount,
            'description' => $this->description,
            'cashbox_id' => $caja->id,
        ];

        if ($this->photo) {
            if ($this->isEditMode) {
                $incomeOld = Income::find($this->income_id);
                if ($incomeOld && $incomeOld->photo) {
                    Storage::disk('public')->delete($incomeOld->photo);
                }
            }
            $path = $this->photo->store('photos', 'public');
            $data['photo'] = $path;
        } else if ($this->isEditMode) {
            $incomeOld = Income::find($this->income_id);
            $data['photo'] = $incomeOld?->photo;
        }

        Income::updateOrCreate(
            ['id' => $this->isEditMode ? $this->income_id : null],
            $data
        );

        $message = $this->isEditMode ? 'Ingreso actualizado exitosamente.' : 'Ingreso creado con éxito.';
        session()->flash('message', $message);

        $this->resetInputFields();
        $this->dispatch('incomeStoreOrUpdate');
    }

    public function edit($id)
    {
        $this->resetValidation();
        $income = Income::findOrFail($id);
        $this->income_id = $id;
        $this->amount = $income->amount;
        $this->description = $income->description;
        $this->photo = null;
        $this->isEditMode = true;
    }

    public function delete($id)
    {
        $income = Income::find($id);
        if ($income) {
            if ($income->photo) {
                Storage::disk('public')->delete($income->photo);
            }
            $income->delete();
            $this->dispatch('incomeDeleted');
        } else {
            session()->flash('message', 'Ingreso no encontrado.');
        }
    }

    public function exportExcel()
    {
        $fileName = 'ingresos_' . Carbon::now()->format('Y_m_d_His') . '.xlsx';
        return Excel::download(new IncomesExport($this->fromDate, $this->toDate, $this->searchTerm), $fileName);
    }
}