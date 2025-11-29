<div>
    <div class="row mb-4">
        <div class="col-md-6">
            <input cashbox="text" wire:model.live="searchTerm" class="form-control" placeholder="Buscar...">
        </div>
        @if ($cajaExiste)
            <div class="col-md-6 text-right">
                <button class="btn btn-primary"
                onclick="confirmCierre()">Cerrar Caja</button>
            </div>
        @else
            <div class="col-md-6 text-right">
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#typeModal"
                    wire:click="resetInputFields">Abrir Caja</button>
            </div>
        @endif

    </div>

    @if (session()->has('message'))
        <div class="alert alert-success">
            {{ session('message') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif

    @if ($cajaExiste)
        <div class="card mb-4">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Movimientos de Caja Abierta</h5>
                <button wire:click="exportMovements" class="btn btn-sm btn-light">
                    <i class="fas fa-file-excel"></i> Exportar Excel
                </button>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-4">
                        <div class="alert alert-info">
                            <strong>Monto Inicial:</strong> {{ number_format($cajaExiste->initial_amount, 2) }}
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="alert alert-success">
                            <strong>Total Ingresos:</strong> {{ number_format($totalIngresos, 2) }}
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="alert alert-danger">
                            <strong>Total Egresos:</strong> {{ number_format($totalEgresos, 2) }}
                        </div>
                    </div>
                </div>

                @if ($movimientos->isEmpty())
                    <div class="alert alert-warning">
                        No hay movimientos registrados en esta caja.
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>Tipo</th>
                                    <th>Descripción</th>
                                    <th>Monto</th>
                                    <th>Fecha/Hora</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($movimientos as $movimiento)
                                    <tr>
                                        <td>
                                            @if ($movimiento['tipo'] == 'Ingreso')
                                                <span class="badge bg-success">{{ $movimiento['tipo'] }}</span>
                                            @else
                                                <span class="badge bg-danger">{{ $movimiento['tipo'] }}</span>
                                            @endif
                                        </td>
                                        <td>{{ $movimiento['descripcion'] }}</td>
                                        <td>{{ number_format($movimiento['monto'], 2) }}</td>
                                        <td>{{ \Carbon\Carbon::parse($movimiento['fecha'])->format('d/m/Y H:i') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    @endif

    <h5 class="mb-3">Historial de Cajas</h5>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Monto Inicial</th>
                <th>Fecha Apertura</th>
                <th>Fecha Cierre</th>
                <th>Gasto</th>
                <th>Estado</th>
                <th>Acción</th>
            </tr>
        </thead>
        <tbody>
            @if ($cashboxs->isEmpty())
                <tr>
                    <td colspan="4" class="text-center">No se encontraron cajas.</td>
                </tr>
            @else
                @foreach ($cashboxs as $cashbox)
                    <tr>
                        <td>{{ $cashbox->initial_amount }}</td>
                        <td>{{ $cashbox->created_at }}</td>
                        <td>{{ $cashbox->closing_date }}</td>
                        <td>{{ $cashbox->spent }}</td>
                        <td>
                            @if ($cashbox->status == 1)
                                <span class="badge bg-warning text-dark">Abierto</span>
                            @else
                                <span class="badge bg-success">Cerrado</span>
                            @endif
                        </td>
                        <td>
                            <button wire:click="edit({{ $cashbox->id }})" class="btn btn-sm btn-primary"
                                data-bs-toggle="modal" data-bs-target="#typeModal">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="confirmDelete({{ $cashbox->id }})">
                                <i class="fas fa-times-circle"></i>
                            </button>
                        </td>
                    </tr>
                @endforeach
            @endif

        </tbody>
    </table>

    {{ $cashboxs->links() }}

    <!-- Property Modal -->
    <div wire:ignore.self class="modal fade" id="typeModal" tabindex="-1" aria-labelledby="typeModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="typeModalLabel">
                        {{ $isEditMode ? 'Editar Caja' : 'Crear Caja' }}</h5>
                    <button cashbox="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    </button>
                </div>
                <div class="modal-body">
                    <form>
                        <div class="form-group">
                            <label for="initial_amount">Monto inicial</label>
                            <input cashbox="text" class="form-control" placeholder="Monto inicial" id="initial_amount"
                                wire:model="initial_amount">
                            @error('initial_amount')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button cashbox="button" class="btn btn-sm btn-danger" data-bs-dismiss="modal">Cancelar</button>
                    <button cashbox="button" wire:click.prevent="storeOrUpdate()"
                        class="btn btn-sm btn-primary">{{ $isEditMode ? 'Actualizar' : 'Guardar' }}</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('livewire:init', function() {
        Livewire.on('cashStoreOrUpdate', function() {
            let modal = bootstrap.Modal.getInstance(document.getElementById('typeModal'));
            if (modal) {
                modal.hide();
            }
        });
    });

    function confirmDelete(id) {
        Swal.fire({
            title: 'Estas seguro?',
            text: "¡Si eliminar se perderan todos los movimientos!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: '¡Sí, bórralo!'
        }).then((result) => {
            if (result.isConfirmed) {
                @this.call('delete', id);
                Swal.fire(
                    'Eliminado!',
                    'Monto inicial ha sido eliminada.',
                    'success'
                );
            }
        });
    }

    function confirmCierre(id) {
        Swal.fire({
            title: 'Estas seguro?',
            text: "¡Cerrar Caja!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: '¡Sí, cerrar!'
        }).then((result) => {
            if (result.isConfirmed) {
                @this.call('cerrarCaja', id);
                Swal.fire(
                    'Aviso!',
                    'Caja Cerrada',
                    'success'
                );
            }
        });
    }
</script>
