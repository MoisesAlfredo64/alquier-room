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

    public function mount($rent)
    {
        $this->rent = $rent;
        $room = Room::findOrFail($this->rent->room_id);
        $rentalPrice = $room->rentalprice;
        $this->total = $rentalPrice;
        
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
    }

    public function storePayment()
    {
        $this->validate([
            'amount' => 'required|numeric'
        ]);

        //COMPORBAR CAJA
        $caja = CashBox::where('status', 1)->first();
        if ($caja) {

            $payment = Payment::create([
                'amount' => $this->amount,
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

        $reading = ElectricityReading::create([
            'client_id' => $this->rent->client_id,
            'rent_id' => $this->rent->id,
            'reading_date' => $this->reading_date,
            'initial_reading' => $this->initial_reading,
            'final_reading' => $this->final_reading,
            'consumption' => $this->consumption,
            'kwh_price' => $this->kwh_price,
            'total_amount' => $this->electricity_amount
        ]);

        $this->lastReading = $reading;
        
        session(null)->flash('message', 'Lectura de luz registrada exitosamente.');
        $this->dispatch('readingStored');
        $this->resetInputFields();
    }

    public function exportElectricityReading($readingId)
    {
        return Excel::download(new ElectricityReadingExport($readingId), 'lectura_luz_' . $readingId . '.xlsx');
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
