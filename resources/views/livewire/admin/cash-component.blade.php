<div>
    <div class="row mb-4">
        <div class="col-md-6">
            <input cashbox="text" wire:model.live="searchTerm" class="form-control" placeholder="Buscar...">
        </div>
        @if ($cajaExiste)
            <div class="col-md-6 text-right">
                <button class="btn btn-primary" onclick="confirmCierre()">Cerrar Caja</button>
            </div>
        @else
            <div class="col-md-6 text-right">
                <button class="btn btn-primary" wire:click="abrirCaja">Abrir Caja</button>
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
                    <div class="col-md-6">
                        <div class="alert alert-success">
                            <strong>Total Ingresos:</strong> {{ number_format($totalIngresos, 2) }}
                        </div>
                    </div>
                    <div class="col-md-6">
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

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">Historial de Cajas</h5>
    </div>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Fecha Apertura</th>
                <th>Fecha Cierre</th>
                <th>Ingresos</th>
                <th>Egreso</th>
                <th>Estado</th>
                <th>Acción</th>
            </tr>
        </thead>
        <tbody>
            @if ($cashboxs->isEmpty())
                <tr>
                    <td colspan="7" class="text-center">No se encontraron cajas.</td>
                </tr>
            @else
                @foreach ($cashboxs as $cashbox)
                    <tr>
                        <td>{{ $cashbox->created_at }}</td>
                        <td>{{ $cashbox->closing_date }}</td>
                        <td>{{ number_format($cashbox->payments_sum_amount ?? 0, 2) }}</td>
                        <td>{{ number_format($cashbox->expenses_sum_amount ?? 0, 2) }}</td>
                        <td>
                            @if ($cashbox->status == 1)
                                <span class="badge bg-warning text-dark">Abierto</span>
                            @else
                                <span class="badge bg-success">Cerrado</span>
                            @endif
                        </td>
                        <td>
                            <button class="btn btn-sm btn-danger" onclick="confirmDelete({{ $cashbox->id }})">
                                <i class="fas fa-times-circle"></i>
                            </button>
                            <button class="btn btn-sm btn-success" wire:click="exportMovements({{ $cashbox->id }})">
                                <i class="fas fa-file-excel"></i> Excel
                            </button>
                        </td>
                    </tr>
                @endforeach
            @endif

        </tbody>
    </table>

    {{ $cashboxs->links() }}

    <!-- Totales por mes -->
    <div class="mt-4">
        <h5>Totales por mes</h5>
        <table class="table table-bordered w-auto">
            <thead>
                <tr>
                    <th>Mes</th>
                    <th>Ingresos</th>
                    <th>Egresos</th>
                    <th>Registro</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($totalesPorMes as $mes => $totales)
                    <tr>
                        <td>{{ \Carbon\Carbon::createFromFormat('Y-m', $mes)->format('F Y') }}</td>
                        <td>{{ number_format($totales['ingreso'], 2) }}</td>
                        <td>{{ number_format($totales['egreso'], 2) }}</td>
                        <td>
                            <button class="btn btn-sm btn-success" wire:click="exportMonth('{{ $mes }}')">
                                <i class="fas fa-file-excel"></i> Descargar
                            </button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="table-dark">
                    <th>Total General</th>
                    <th>{{ number_format($ingresoGeneral, 2) }}</th>
                    <th>{{ number_format($egresoGeneral, 2) }}</th>
                    <th>
                        <button class="btn btn-sm btn-light" wire:click="exportAllMovements">
                            <i class="fas fa-file-excel"></i> General
                        </button>
                    </th>
                </tr>
            </tfoot>
        </table>
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
                    'Caja eliminada.',
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
                @this.call('cerrarCaja');
                Swal.fire(
                    'Aviso!',
                    'Caja Cerrada',
                    'success'
                );
            }
        });
    }
</script>
