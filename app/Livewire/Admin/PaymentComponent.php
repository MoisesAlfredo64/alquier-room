<?php

namespace App\Livewire\Admin;

use App\Models\CashBox;
use App\Models\Payment;
use App\Models\Room;
use App\Models\ElectricityReading;
use App\Exports\ElectricityReadingExport;
use Illuminate\Support\Facades\Crypt;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class PaymentComponent extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $rent;
    public $searchTerm = '';
    public $amount;
    public $payment_date;
    public $total = 0;
    
    // Propiedades para lectura de luz
    public $reading_date;
    public $initial_reading;
    public $final_reading;
    public $kwh_price;
    public $consumption = 0;
    public $electricity_amount = 0;
    public $lastReading;
    public $reading_id;
    public $isEditMode = false;

    public function mount($rent)
    {
        $this->rent = $rent;
        $room = Room::findOrFail($this->rent->room_id);
        $rentalPrice = $room->rentalprice;
        $parkingPrice = ($this->rent->uses_parking && $room->parking_price) ? $room->parking_price : 0;
        $this->total = $rentalPrice + $parkingPrice;
        
        // Obtener la última lectura del mes anterior
        $this->lastReading = ElectricityReading::where('rent_id', $this->rent->id)
            ->orderBy('reading_date', 'desc')
            ->first();
    }

    public function resetInputFields()
    {
        $this->amount = '';
        $this->payment_date = '';
        $this->reading_date = '';
        $this->initial_reading = '';
        $this->final_reading = '';
        $this->kwh_price = '';
        $this->consumption = 0;
        $this->electricity_amount = 0;
        $this->reading_id = '';
        $this->isEditMode = false;
    }

    public function storePayment()
    {
        $this->validate([
            'amount' => 'required|numeric',
            'payment_date' => 'required|date'
        ]);

        // Obtener la última lectura de luz
        $lastReading = ElectricityReading::where('rent_id', $this->rent->id)
            ->orderBy('reading_date', 'desc')
            ->first();
        
        // Validar que exista una lectura con importe mayor a cero
        if (!$lastReading || $lastReading->total_amount == 0) {
            session(null)->flash('warning', 'Debe ingresar el importe de luz primero');
            return;
        }

        // Validar que la fecha de pago coincida con la fecha de lectura de luz
        $readingDate = \Carbon\Carbon::parse($lastReading->reading_date)->format('Y-m-d');
        $paymentDate = \Carbon\Carbon::parse($this->payment_date)->format('Y-m-d');
        
        if ($readingDate !== $paymentDate) {
            $formattedDate = \Carbon\Carbon::parse($lastReading->reading_date)->format('d/m/Y');
            session(null)->flash('warning', 'La fecha de pago debe coincidir con la fecha de lectura de luz (' . $formattedDate . ')');
            return;
        }

        //COMPORBAR CAJA
        $caja = CashBox::where('status', 1)->first();
        if ($caja) {

            $payment = Payment::create([
                'amount' => $this->amount,
                'payment_date' => $this->payment_date,
                'rent_id' => $this->rent->id,
                'cashbox_id' => $caja->id
            ]);

            $pdfPath = route('payment.pdf', ['id' => Crypt::encrypt($payment->id)]);

            session(null)->flash('message', 'Pago agregado exitosamente. El ticket se abrirá en una nueva ventana.');

            $this->dispatch('paymentStored', ['pdfPath' => $pdfPath]);

            $this->resetInputFields();
        } else {
            session(null)->flash('warning', 'La caja esta cerrada');
        }
    }

    public function calculateConsumption()
    {
        if ($this->initial_reading && $this->final_reading && $this->kwh_price) {
            $this->consumption = $this->final_reading - $this->initial_reading;
            $this->electricity_amount = $this->consumption * $this->kwh_price;
        }
    }

    public function storeElectricityReading()
    {
        $this->validate([
            'reading_date' => 'required|date',
            'initial_reading' => 'required|numeric',
            'final_reading' => 'required|numeric',
            'kwh_price' => 'required|numeric'
        ], [
            'reading_date.required' => 'La fecha de lectura es obligatoria.',
            'initial_reading.required' => 'La lectura inicial es obligatoria.',
            'final_reading.required' => 'La lectura final es obligatoria.',
            'kwh_price.required' => 'El precio por KWH es obligatorio.'
        ]);

        $this->calculateConsumption();

        $data = [
            'client_id' => $this->rent->client_id,
            'rent_id' => $this->rent->id,
            'reading_date' => $this->reading_date,
            'initial_reading' => $this->initial_reading,
            'final_reading' => $this->final_reading,
            'consumption' => $this->consumption,
            'kwh_price' => $this->kwh_price,
            'total_amount' => $this->electricity_amount
        ];

        if ($this->isEditMode) {
            $reading = ElectricityReading::findOrFail($this->reading_id);
            $reading->update($data);
            $message = 'Lectura de luz actualizada exitosamente.';
        } else {
            $reading = ElectricityReading::create($data);
            $message = 'Lectura de luz registrada exitosamente.';
        }

        $this->lastReading = $reading;
        
        session(null)->flash('message', $message);
        $this->dispatch('readingStored');
        $this->resetInputFields();
    }

    public function exportElectricityReading($readingId)
    {
        return Excel::download(new ElectricityReadingExport($readingId), 'lectura_luz_' . $readingId . '.xlsx');
    }

    public function exportAllElectricityReadings()
    {
        $readings = ElectricityReading::where('rent_id', $this->rent->id)
            ->orderBy('reading_date', 'desc')
            ->get();
        
        return Excel::download(
            new \App\Exports\AllElectricityReadingsExport($this->rent->id),
            'lecturas_luz_' . $this->rent->client->full_name . '_' . now()->format('Y-m-d') . '.xlsx'
        );
    }

    public function editReading($id)
    {
        $this->resetValidation();
        $reading = ElectricityReading::findOrFail($id);
        $this->reading_id = $id;
        $this->reading_date = $reading->reading_date->format('Y-m-d');
        $this->initial_reading = $reading->initial_reading;
        $this->final_reading = $reading->final_reading;
        $this->kwh_price = $reading->kwh_price;
        $this->consumption = $reading->consumption;
        $this->electricity_amount = $reading->total_amount;
        $this->isEditMode = true;
    }

    public function deleteReading($id)
    {
        $reading = ElectricityReading::find($id);
        if ($reading) {
            $reading->delete();
            
            // Actualizar lastReading
            $this->lastReading = ElectricityReading::where('rent_id', $this->rent->id)
                ->orderBy('reading_date', 'desc')
                ->first();
            
            session(null)->flash('message', 'Lectura de luz eliminada correctamente.');
            $this->dispatch('readingDeleted');
        }
    }

    public function render()
    {
        $payments = Payment::where('created_at', 'like', '%' . $this->searchTerm . '%')
            ->where('rent_id', $this->rent->id)
            ->orderBy('id', 'desc')
            ->paginate(8);

        $electricityReadings = ElectricityReading::where('rent_id', $this->rent->id)
            ->orderBy('reading_date', 'desc')
            ->paginate(5);

        return view('livewire.admin.payment-component', compact('payments', 'electricityReadings'));
    }
}
