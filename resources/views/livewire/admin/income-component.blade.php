<div>
    <div class="row mb-4">
        <div class="col-md-6">
            <input type="text" wire:model.live="searchTerm" class="form-control" placeholder="Buscar...">
        </div>
        <div class="col-md-6 text-end">
            <button class="btn btn-success me-2" wire:click="exportExcel" title="Exportar a Excel">
                <i class="fas fa-file-excel"></i>
            </button>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#incomeModal" wire:click="resetInputFields" title="Nuevo ingreso">
                <i class="fas fa-plus-circle"></i>
            </button>
        </div>
    </div>

    @if (session()->has('message'))
        <div class="alert alert-success">{{ session('message') }}</div>
    @endif
    @if (session()->has('warning'))
        <div class="alert alert-warning">{{ session('warning') }}</div>
    @endif

    <div class="d-flex gap-3 mb-3">
        <div class="form-group">
            <label for="desde">Desde</label>
            <input id="desde" class="form-control" type="date" wire:model.live="fromDate">
        </div>
        <div class="form-group">
            <label for="hasta">Hasta</label>
            <input id="hasta" class="form-control" type="date" wire:model.live="toDate">
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Monto</th>
                    <th>Fecha/Hora</th>
                    <th>Descripción</th>
                    <th>Foto</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
                @if ($incomes->isEmpty())
                    <tr>
                        <td colspan="5" class="text-center">No se encontraron ingresos.</td>
                    </tr>
                @else
                    @foreach ($incomes as $income)
                        <tr>
                            <td>{{ number_format($income->amount, 2) }}</td>
                            <td>{{ \Carbon\Carbon::parse($income->created_at)->format('d/m/Y H:i') }}</td>
                            <td>{{ $income->description }}</td>
                            <td>
                                @if ($income->photo)
                                    <a href="{{ asset('storage/' . $income->photo) }}" target="_blank">
                                        <img src="{{ asset('storage/' . $income->photo) }}" width="90" alt="Foto" style="cursor: pointer;">
                                    </a>
                                @else
                                    <span>No disponible</span>
                                @endif
                            </td>
                            <td>
                                <button wire:click="edit({{ $income->id }})" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#incomeModal">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="confirmDelete({{ $income->id }})">
                                    <i class="fas fa-times-circle"></i>
                                </button>
                            </td>
                        </tr>
                    @endforeach
                @endif
            </tbody>
        </table>
    </div>

    {{ $incomes->links() }}

    <div wire:ignore.self class="modal fade" id="incomeModal" tabindex="-1" aria-labelledby="incomeModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="incomeModalLabel">{{ $isEditMode ? 'Editar ingreso' : 'Crear ingreso' }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form wire:submit.prevent="storeOrUpdate" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="form-group mb-2">
                            <label for="amount">Monto</label>
                            <input type="text" id="amount" class="form-control" wire:model="amount" placeholder="Monto">
                            @error('amount') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                        <div class="form-group mb-2">
                            <label for="description">Descripción</label>
                            <input type="text" id="description" class="form-control" wire:model="description" placeholder="Descripción">
                            @error('description') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                        <div class="form-group mb-2">
                            <label for="photo">Foto</label>
                            <input type="file" id="photo" class="form-control" wire:model="photo">
                            @error('photo') <span class="text-danger">{{ $message }}</span> @enderror
                            @if ($photo)
                                <img src="{{ $photo->temporaryUrl() }}" width="90" class="mt-2" />
                            @endif
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-sm btn-danger" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-sm btn-primary">{{ $isEditMode ? 'Actualizar' : 'Guardar' }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('livewire:init', () => {
        Livewire.on('incomeStoreOrUpdate', () => {
            let modal = bootstrap.Modal.getInstance(document.getElementById('incomeModal'));
            if (modal) modal.hide();
        });
    });

    function confirmDelete(id) {
        Swal.fire({
            title: '¿Estas seguro?',
            text: '¡No podrás revertir esto!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: '¡Sí, bórralo!'
        }).then((result) => {
            if (result.isConfirmed) {
                @this.call('delete', id);
                Swal.fire('Eliminado!', 'Ingreso eliminado.', 'success');
            }
        });
    }
</script>