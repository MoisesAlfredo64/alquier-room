<?php

namespace App\Livewire\Admin;

use App\Models\Client;
use Livewire\Component;
use Livewire\WithPagination;

class ClientComponent extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';
    public $full_name, $date_of_birth, $gender, $phone, $email, $address, $city, $state, $postal_code, $country, $identification_number, $identification_type;
    public $client_id;
    public $isEditMode = false;
    public $searchTerm;

    protected $listeners = ['delete'];

    protected function rules()
    {
        $rules = [
            'full_name' => 'required|string',
            'city' => 'required|string',
            'address' => 'required|string',
            'phone' => 'required|string',
            'email' => 'nullable|email',
            'date_of_birth' => 'nullable|date',
            'gender' => 'required|string',
            'state' => 'nullable|string',
            'postal_code' => 'nullable|string',
            'country' => 'nullable|string',
            'identification_number' => 'required|string',
            'identification_type' => 'required|string'
        ];

        if ($this->isEditMode) {
            $rules['email'] .= '|unique:clients,email,' . $this->client_id;
        } else {
            $rules['email'] .= '|unique:clients,email';
        }

        return $rules;
    }

    public function render()
    {
        // Obtener IDs de clientes con alquileres activos
        $activeClientIds = \App\Models\Rent::where('status', 1)
            ->pluck('client_id')
            ->toArray();

        $clients = Client::query()
            ->where(function ($q) {
                $q->where('full_name', 'like', '%' . $this->searchTerm . '%')
                    ->orWhere('city', 'like', '%' . $this->searchTerm . '%')
                    ->orWhere('address', 'like', '%' . $this->searchTerm . '%')
                    ->orWhere('client_number', 'like', '%' . $this->searchTerm . '%');
            })
            ->orderByRaw('CASE WHEN id IN (' . implode(',', array_merge($activeClientIds, [0])) . ') THEN 0 ELSE 1 END')
            ->orderBy('id', 'desc')
            ->paginate(10);

        return view('livewire.admin.client-component', [
            'clients' => $clients,
            'activeClientIds' => $activeClientIds
        ])
            ->extends('admin.layouts.app');
    }

    public function resetInputFields()
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
        $this->client_id = '';
        $this->isEditMode = false;
    }

    public function storeOrUpdate()
    {
        $this->validate(
            $this->rules(),
            [
                'full_name.required' => 'El nombre completo es obligatorio.',
                'city.required' => 'La ciudad es obligatoria.',
                'address.required' => 'La dirección es obligatoria.',
                'phone.required' => 'El teléfono es obligatorio.',
                'gender.required' => 'El género es obligatorio.',
                'identification_number.required' => 'El número de identificación es obligatorio.',
                'identification_type.required' => 'El tipo de identificación es obligatorio.'
            ]
        );

        $data = [
            'full_name' => $this->full_name,
            'date_of_birth' => $this->date_of_birth,
            'gender' => $this->gender,
            'phone' => $this->phone,
            'email' => $this->email,
            'address' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'postal_code' => $this->postal_code,
            'country' => $this->country,
            'identification_number' => $this->identification_number,
            'identification_type' => $this->identification_type
        ];

        // Generar client_number si es nuevo cliente
        if (!$this->isEditMode) {
            $maxClientNumber = Client::max('client_number') ?? 0;
            $data['client_number'] = $maxClientNumber + 1;
        }

        Client::updateOrCreate(
            ['id' => $this->client_id],
            $data
        );

        $message = $this->isEditMode ? 'Cliente actualizado exitosamente.' : 'Cliente creado con éxito.';
        session(null)->flash('message', $message);

        $this->resetInputFields();
        $this->dispatch('clientStoreOrUpdate');
    }

    public function edit($id)
    {
        $this->resetValidation();
        $client = Client::findOrFail($id);
        $this->client_id = $id;
        $this->full_name = $client->full_name;
        $this->date_of_birth = $client->date_of_birth;
        $this->gender = $client->gender;
        $this->phone = $client->phone;
        $this->email = $client->email;
        $this->address = $client->address;
        $this->city = $client->city;
        $this->state = $client->state;
        $this->postal_code = $client->postal_code;
        $this->country = $client->country;
        $this->identification_number = $client->identification_number;
        $this->identification_type = $client->identification_type;
        $this->isEditMode = true;
    }

    public function delete($id)
    {
        $client = Client::find($id);
        if (!$client) {
            session(null)->flash('message', 'Cliente no encontrado.');
            return;
        }

        // Verificar si tiene algún alquiler (activo o histórico)
        $hasRents = \App\Models\Rent::where('client_id', $id)->exists();

        if ($hasRents) {
            $activeRent = \App\Models\Rent::where('client_id', $id)
                ->where('status', 1)
                ->with('room')
                ->first();

            if ($activeRent) {
                $roomName = $activeRent->room && $activeRent->room->room_number 
                    ? $activeRent->room->room_number 
                    : ($activeRent->room ? 'Habitación ' . $activeRent->room->number : 'una habitación');
                $msg = $client->full_name . ' no puede ser eliminado porque está ocupando ' . $roomName;
            } else {
                $msg = $client->full_name . ' no puede ser eliminado porque tiene alquileres registrados en el historial.';
            }
            
            session(null)->flash('message', $msg);
            $this->dispatch('clientDeleteBlocked', message: $msg);
            return;
        }

        // Si no tiene alquileres, permitir eliminación
        $client->delete();
        $this->dispatch('clientDeleted');
        session(null)->flash('message', 'Cliente eliminado exitosamente.');
    }
}
