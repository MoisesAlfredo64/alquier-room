<?php

namespace App\Livewire\Admin;

use App\Models\Client;
use App\Models\Rent;
use App\Models\Room;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;

class RentComponent extends Component
{
    use WithPagination, WithFileUploads;
    protected $paginationTheme = 'bootstrap';

    public $room;
    public $searchTerm;
    public $full_name, $date_of_birth, $gender, $phone, $email, $address, $city, $state, $postal_code, $country, $identification_number, $identification_type, $note, $contract_photo, $client_id;

    public function mount(Room $room)
    {
        $this->room = $room;
    }

    public function store()
    {
        $this->validate([
            'full_name' => 'required|string',
            'city' => 'required|string',
            'address' => 'required|string',
            'phone' => 'nullable|string',
            'email' => 'nullable|email',
            'date_of_birth' => 'required|date',
            'gender' => 'nullable|string',
            'state' => 'nullable|string',
            'postal_code' => 'nullable|string',
            'country' => 'nullable|string',
            'identification_number' => 'nullable|string',
            'identification_type' => 'nullable|string'
        ]);

        // Generar client_number automático
        $maxClientNumber = Client::max('client_number') ?? 0;

        $client = Client::create([
            'client_number' => $maxClientNumber + 1,
            'full_name' => $this->full_name,
            'date_of_birth' => !empty($this->date_of_birth) ? $this->date_of_birth : null,
            'gender' => !empty($this->gender) ? $this->gender : null,
            'phone' => !empty($this->phone) ? $this->phone : null,
            'email' => !empty($this->email) ? $this->email : null,
            'address' => $this->address,
            'city' => $this->city,
            'state' => !empty($this->state) ? $this->state : null,
            'postal_code' => !empty($this->postal_code) ? $this->postal_code : null,
            'country' => !empty($this->country) ? $this->country : null,
            'identification_number' => !empty($this->identification_number) ? $this->identification_number : null,
            'identification_type' => !empty($this->identification_type) ? $this->identification_type : null,
        ]);

        $cliente = Client::findOrFail($client->id);
        $this->client_id = $cliente->id;
        $this->dispatch('clientAdd', [
            'message' => 'Cliente Seleccionado.',
            'client' => $cliente->full_name,
            'phone' => $cliente->phone,
            'address' => $cliente->address
        ]);

        $this->resetInputFields();

        $this->dispatch('clientStore', ['message' => 'Cliente creado exitosamente.']);
    }


    private function resetInputFields()
    {
        $this->full_name = '';
        $this->date_of_birth = '';
        $this->gender = '';
        $this->phone = '';
        $this->email = '';
        $this->address = '';
        $this->city = '';
        $this->state = '';
        $this->postal_code = '';
        $this->country = '';
        $this->identification_number = '';
        $this->identification_type = '';
    }

    public function addClient($client_id)
    {
        $client = Client::findOrFail($client_id);
        $this->client_id = $client_id;
        $this->dispatch('clientAdd', [
            'message' => 'Cliente Seleccionado.',
            'client' => $client->full_name,
            'phone' => $client->phone,
            'address' => $client->address
        ]);
    }

    public function rent()
    {
        $this->validate([
            'client_id' => 'required|numeric',
            'note' => 'nullable|string',
            'contract_photo' => 'nullable|file|mimes:pdf,png,jpg,jpeg|max:5120'
        ]);

        // Generar rent_number automático (ALQ-0001, ALQ-0002, etc.)
        $maxRentCount = Rent::count();
        $rentNumber = 'ALQ-' . str_pad($maxRentCount + 1, 4, '0', STR_PAD_LEFT);

        $data = [
            'rent_number' => $rentNumber,
            'note' => $this->note,
            'client_id' => $this->client_id,
            'room_id' => $this->room->id
        ];

        // Guardar foto del contrato si existe
        if ($this->contract_photo) {
            $path = $this->contract_photo->store('contracts', 'public');
            $data['contract_photo'] = $path;
        }

        Rent::create($data);

        // Redirige a una ruta específica después de la creación
        return redirect()->route('rooms.index')->with('message', '¡Alquiler creado con éxito!');
    }

    public function render()
    {
        $clients = Client::where('full_name', 'like', '%' . $this->searchTerm . '%')
            ->orWhere('phone', 'like', '%' . $this->searchTerm . '%')
            ->orWhere('address', 'like', '%' . $this->searchTerm . '%')
            ->orderBy('id', 'desc')
            ->paginate(10);

        return view('livewire.admin.rent-component', compact('clients'));
    }
}
