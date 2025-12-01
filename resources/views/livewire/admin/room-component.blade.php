<div>
    <div class="row mb-4">
        <div class="col-md-6">
            <input type="text" wire:model.live="searchTerm" class="form-control" placeholder="Buscar...">
        </div>
        <div class="col-md-6 text-right">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#roomModal"
                wire:click="resetInputFields"><i class="fas fa-plus-circle"></i></button>
        </div>
    </div>

    @if (session()->has('message'))
        <div class="alert alert-success">
            {{ session('message') }}
        </div>
    @endif

    <div class="row">
        @if ($rooms->isEmpty())
            <div class="col-md-12">
                No se encontraron habitaciones.
            </div>
        @else
            @foreach ($rooms as $room)
                <div class="col-md-4 mb-2">
                    <div
                        class="card shadow-lg">
                        <div class="card-header fw-bold fs-5 text-center {{ in_array($room->id, $rents) ? 'bg-danger text-white' : 'bg-success text-white' }}">
                            {{ $room->property->name }}
                        </div>
                        <div class="card-body">
                            <h5 class="card-title fs-4">
                                {{ $room->rentalprice }} <span class="text-secondary">/ Mensual</span>
                            </h5>
                            <ul class="list-unstyled">
                                <li><b class="text-dark">Tipo:</b> {{ $room->type->name }}</li>
                                <li><b class="text-dark">Personas:</b> {{ $room->people_count }}</li>
                                @if (in_array($room->id, $rents))
                                    <li><b class="text-dark">Ocupa:</b> {{ $occupants[$room->id] ?? '—' }}</li>
                                @endif
                            </ul>
                            <div class="mb-2">
                                <span class="badge bg-primary">{{ $room->room_number }}</span>
                                <span class="badge bg-warning">N° {{ $room->number }}</span>
                            </div>

                            <!-- Verifica si el room_id está en la lista de rents -->
                            @if (in_array($room->id, $rents))
                                <button class="btn btn-info btn-sm" type="button" onclick="confirmLiberar({{ $room->id }})">Liberar</button>
                            @else
                                <a class="btn btn-sm btn-info"
                                    href="{{ route('rent.index', Crypt::encrypt($room->id)) }}">
                                    <i class="fa-solid fa-person-booth"></i>
                                </a>
                            @endif

                            <button wire:click="edit({{ $room->id }})" class="btn btn-sm btn-primary"
                                data-bs-toggle="modal" data-bs-target="#roomModal">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="confirmDelete({{ $room->id }})">
                                <i class="fas fa-times-circle"></i>
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        @endif
    </div>


    {{ $rooms->links() }}

    <!-- Property Modal -->
    <div wire:ignore.self class="modal fade" id="roomModal" tabindex="-1" aria-labelledby="roomModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="roomModalLabel">
                        {{ $isEditMode ? 'Editar habitacion' : 'Crear habitacion' }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    </button>
                </div>
                <div class="modal-body">
                    <form class="row">
                        <div class="form-group col-md-6">
                            <label for="property_id">Propiedad</label>
                            <select id="property_id" class="form-control form-control-lg" wire:model="property_id">
                                <option value="">Seleccionar</option>
                                @foreach ($properties as $property)
                                    <option value="{{ $property->id }}">{{ $property->name }}</option>
                                @endforeach
                            </select>
                            @error('property_id')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="form-group col-md-6">
                            <label for="type_id">Tipo</label>
                            <select id="type_id" class="form-control form-control-lg" wire:model="type_id">
                                <option value="">Seleccionar</option>
                                @foreach ($types as $type)
                                    <option value="{{ $type->id }}">{{ $type->name }}</option>
                                @endforeach
                            </select>
                            @error('type_id')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="form-group col-md-6">
                            <label for="rentalprice">Precio Alquiler</label>
                            <input type="text" class="form-control" placeholder="Precio Alquiler" id="rentalprice"
                                wire:model="rentalprice">
                            @error('rentalprice')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="form-group col-md-6">
                            <label for="parking_price">Precio Estacionamiento Extra</label>
                            <input type="text" class="form-control" placeholder="Precio Estacionamiento" id="parking_price"
                                wire:model="parking_price">
                        </div>
                        <div class="form-group col-md-6">
                            <label for="warranty">Garantía</label>
                            <input type="text" class="form-control" placeholder="Monto Garantía" id="warranty"
                                wire:model="warranty">
                        </div>
                        <div class="form-group col-md-6">
                            <label for="number">Numero</label>
                            <input type="number" class="form-control" placeholder="Numero" id="number"
                                wire:model="number">
                            @error('number')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="form-group col-md-6">
                            <label for="people_count">Cantidad de personas</label>
                            <input type="number" min="1" class="form-control" placeholder="Cantidad de personas" id="people_count"
                                wire:model="people_count">
                            @error('people_count')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <!-- Campo Precio Luz eliminado -->
                        <!-- Campo Precio Agua eliminado -->
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-danger" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" wire:click.prevent="storeOrUpdate()"
                        class="btn btn-sm btn-primary">{{ $isEditMode ? 'Actualizar' : 'Guardar' }}</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('livewire:init', function() {
        Livewire.on('roomStoreOrUpdate', function() {
            let modal = bootstrap.Modal.getInstance(document.getElementById('roomModal'));
            if (modal) {
                modal.hide();
            }
        });
        Livewire.on('roomDeleteBlocked', function(message) {
            Swal.fire('Acción bloqueada', message, 'error');
        });
    });

    function confirmDelete(id) {
        Swal.fire({
            title: 'Estas seguro?',
            text: "¡No podrás revertir esto!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: '¡Sí, bórralo!'
        }).then((result) => {
            if (result.isConfirmed) {
                @this.call('delete', id);
                // El mensaje de éxito o bloqueo se maneja vía eventos y flash
            }
        });
    }

    function confirmLiberar(id) {
        Swal.fire({
            title: 'Estas seguro?',
            text: "¡No podrás revertir esto!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: '¡Sí, liberar!'
        }).then((result) => {
            if (result.isConfirmed) {
                @this.call('liberar', id);
                Swal.fire(
                    'Liberado!',
                    'Alquiler ha sido liberado.',
                    'success'
                );
            }
        });
    }
</script>
