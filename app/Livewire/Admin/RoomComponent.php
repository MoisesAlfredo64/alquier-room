<?php

namespace App\Livewire\Admin;

use App\Models\Property;
use App\Models\Rent;
use App\Models\Room;
use App\Models\Type;
use Livewire\Component;
use Livewire\WithPagination;

class RoomComponent extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';
    public $rentalprice, $parking_price, $warranty, $number, $people_count, $type_id, $property_id, $room_id;
    public $isEditMode = false;
    public $searchTerm;
    public $types = [];
    public $properties = [];

    protected $listeners = ['delete'];

    protected $rules = [
        'rentalprice' => 'required',
        'number' => 'required|numeric',
        'people_count' => 'required|integer|min:1',
        'type_id' => 'required|numeric',
        'property_id' => 'required|numeric',
    ];

    public function mount()
    {
        $this->types = Type::all();
        $this->properties = Property::all();
    }

    public function render()
    {
        $rooms = Room::with(['property', 'type'])
            ->where(function ($query) {
                $query->where('rentalprice', 'like', '%' . $this->searchTerm . '%')
                    ->orWhere('number', 'like', '%' . $this->searchTerm . '%')
                    ->orWhere('room_number', 'like', '%' . $this->searchTerm . '%')
                    ->orWhereHas('property', function ($query) {
                        $query->where('name', 'like', '%' . $this->searchTerm . '%');
                    })
                    ->orWhereHas('type', function ($query) {
                        $query->where('name', 'like', '%' . $this->searchTerm . '%');
                    });
            })
            ->orderBy('id', 'desc')
            ->paginate(9);

        // Verifica si el room_id está en la tabla rents con status = 1 y obtiene nombre del cliente
        $activeRents = Rent::with('client')
            ->where('status', 1)
            ->get();

        $rents = $activeRents->pluck('room_id')->toArray();
        $occupants = [];
        foreach ($activeRents as $rent) {
            $occupants[$rent->room_id] = $rent->client?->full_name;
        }

        return view('livewire.admin.room-component', [
            'rooms' => $rooms,
            'rents' => $rents,
            'occupants' => $occupants,
        ])
            ->extends('admin.layouts.app');
    }

    public function resetInputFields()
    {
        $this->rentalprice = '';
        $this->parking_price = '';
        $this->warranty = '';
        $this->number = '';
        $this->people_count = 1;
        $this->type_id = '';
        $this->property_id = '';
        $this->room_id = '';
        $this->isEditMode = false;
    }

    public function storeOrUpdate()
    {
        $this->validate(
            $this->rules,
            [
                'rentalprice.required' => 'El monto de alquiler es obligatorio.',
                'number.required' => 'El número de habitación es obligatorio.',
                'number.numeric' => 'El número de habitación debe ser numérico.',
                'people_count.required' => 'La cantidad de personas es obligatoria.',
                'people_count.integer' => 'La cantidad de personas debe ser un entero.',
                'people_count.min' => 'La cantidad de personas debe ser al menos 1.',
                'type_id.required' => 'El tipo de habitación es obligatorio.',
                'type_id.numeric' => 'El tipo de habitación debe ser numérico.',
                'property_id.required' => 'La propiedad es obligatoria.',
                'property_id.numeric' => 'La propiedad debe ser numérica.'
            ]
        );

        $data = [
            'rentalprice' => $this->rentalprice,
            'parking_price' => $this->parking_price,
            'warranty' => $this->warranty,
            'number' => $this->number,
            'people_count' => $this->people_count,
            'type_id' => $this->type_id,
            'property_id' => $this->property_id,
        ];

        // Generar room_number si es nueva habitación
        if (!$this->isEditMode) {
            $maxRoomCount = Room::count();
            $data['room_number'] = 'R-' . str_pad($maxRoomCount + 1, 3, '0', STR_PAD_LEFT);
        }

        Room::updateOrCreate(
            ['id' => $this->isEditMode ? $this->room_id : null],
            $data
        );

        $message = $this->isEditMode ? 'Habitación actualizada exitosamente.' : 'Habitación creada con éxito.';
        session(null)->flash('message', $message);

        $this->resetInputFields();
        $this->dispatch('roomStoreOrUpdate');
    }

    public function edit($id)
    {
        $this->resetValidation();
        $room = Room::findOrFail($id);
        $this->room_id = $id;
        $this->rentalprice = $room->rentalprice;
        $this->parking_price = $room->parking_price;
        $this->warranty = $room->warranty;
        $this->number = $room->number;
        $this->people_count = $room->people_count;
        $this->type_id = $room->type_id;
        $this->property_id = $room->property_id;
        $this->isEditMode = true;
    }

    public function delete($id)
    {
        $room = Room::find($id);
        if (!$room) {
            session(null)->flash('message', 'Habitación no encontrada.');
            return;
        }

        // Bloquear eliminación si existe alquiler activo asociado
        $ocupada = Rent::where('room_id', $id)->where('status', 1)->exists();
        if ($ocupada) {
            $msg = 'La habitación está ocupada y no puede eliminarse. Libérela primero.';
            session(null)->flash('message', $msg);
            $this->dispatch('roomDeleteBlocked', message: $msg);
            return;
        }

        $room->delete();
        $this->dispatch('roomDeleted');
        session(null)->flash('message', 'Habitación eliminada correctamente.');
    }

    //LIBERAR
    public function liberar($id) {
        Rent::where('status', 1)->where('room_id', $id)->update([
            'status' => 0
        ]);
        session(null)->flash('message', 'Alquiler Liberado');
    }
}
