<div>
    @php
        use Carbon\Carbon;
    @endphp
    <div class="row mb-4">
        <div class="col-md-6">
            <input type="text" wire:model.live="searchTerm" class="form-control" placeholder="Buscar...">
        </div>
        <div class="col-md-6 text-end">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#paymentModal"
                wire:click="resetInputFields">
                <i class="fas fa-plus-circle"></i>
            </button>
        </div>
    </div>

    @if (session()->has('message'))
        <div class="alert alert-success">
            {{ session('message') }}
        </div>
    @endif

    @php
        // Calcular total mensual considerando estacionamiento si aplica
        $baseTotal = $rent->room->rentalprice;
        $parking = ($rent->uses_parking && $rent->room->parking_price) ? $rent->room->parking_price : 0;
        
        // Obtener la última lectura de luz registrada (lastReading ya está cargada en el componente)
        $electricityCost = $lastReading ? $lastReading->total_amount : 0;
        
        $total = $baseTotal + $parking + $electricityCost;

        // Suma total de los abonos
        $totalAbonos = $payments->sum('amount');

        $contador = $total > 0 ? $totalAbonos / $total : 0;

        $meses = $contador;

        $fechaInicio = Carbon::parse($rent->created_at);

        // Fecha final basada en el número de meses
        $fechaFinal = $fechaInicio->copy()->addMonths($meses);
    @endphp

    <div class="alert alert-info mb-3">
        <h5 class="mb-2">Desglose de Pago Mensual</h5>
        <table class="table table-sm mb-0">
            <tr>
                <td><strong>Alquiler habitación:</strong></td>
                <td class="text-end">${{ number_format($baseTotal, 2) }}</td>
            </tr>
            @if($parking > 0)
            <tr>
                <td><strong>Estacionamiento Extra:</strong></td>
                <td class="text-end">${{ number_format($parking, 2) }}</td>
            </tr>
            @endif
            <tr>
                <td><strong>Consumo de Luz:</strong></td>
                <td class="text-end">
                    @if($electricityCost > 0)
                        ${{ number_format($electricityCost, 2) }}
                    @else
                        <span class="text-danger">Pendiente</span>
                    @endif
                </td>
            </tr>
            <tr class="table-primary">
                <td><strong>Total Mensual:</strong></td>
                <td class="text-end"><strong>${{ number_format($total, 2) }}</strong></td>
            </tr>
        </table>
    </div>

    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Monto</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
                @if ($payments->isEmpty())
                    <tr>
                        <td colspan="3" class="text-center">No se encontraron pagos.</td>
                    </tr>
                @else
                    @foreach ($payments as $payment)
                        <tr>
                            <td>{{ Carbon::parse($payment['created_at'])->format('d') }} de
                                {{ Carbon::parse($payment['created_at'])->translatedFormat('F') }} de
                                {{ Carbon::parse($payment['created_at'])->format('Y') }} a las
                                {{ Carbon::parse($payment['created_at'])->format('H:i') }}</td>
                            <td>{{ $payment['amount'] }}</td>
                            <td>
                                <a target="_blank" href="{{ route('payment.pdf', Crypt::encrypt($payment->id)) }}"
                                    class="btn btn-danger btn-sm"><i class="fas fa-print"></i></a>
                            </td>
                        </tr>
                    @endforeach
                    <tr>
                        <td><b>Total Abonos:</b></td>
                        <td colspan="2">
                            <h4>{{ $totalAbonos }}</h4>
                        </td>
                    </tr>
                    <tr>
                        <td><b>Meses:</b></td>
                        <td colspan="2">
                            <h4>{{ number_format($contador, 2) }}</h4>
                        </td>
                    </tr>
                    <tr>
                        <td><b>Fecha Final:</b></td>
                        <td colspan="2">
                            <h4>{{ $fechaFinal->format('d') }} de {{ $fechaFinal->translatedFormat('F') }} de
                                {{ $fechaFinal->format('Y') }}</h4>
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>

    <div class="mt-2">
        {{ $payments->links() }}
    </div>

    <!-- Sección de Lecturas de Luz -->
    <div class="card mt-4">
        <div class="card-header bg-warning">
            <h5 class="mb-0">Lecturas de Luz</h5>
        </div>
        <div class="card-body">
            @if($lastReading)
                <div class="alert alert-info">
                    <h6>Última Lectura Registrada:</h6>
                    <p><strong>Fecha:</strong> {{ $lastReading->reading_date->format('d/m/Y') }}</p>
                    <p><strong>Consumo:</strong> {{ number_format($lastReading->consumption, 2) }} KWH</p>
                    <p><strong>Importe:</strong> ${{ number_format($lastReading->total_amount, 2) }}</p>
                </div>
            @else
                <div class="alert alert-warning">
                    No hay lecturas de luz registradas para este inquilino.
                </div>
            @endif

            <button class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#electricityModal"
                wire:click="resetInputFields">
                <i class="fas fa-bolt"></i> Registrar Lectura de Luz
            </button>

            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Lectura Inicial</th>
                            <th>Lectura Final</th>
                            <th>Consumo (KWH)</th>
                            <th>Precio KWH</th>
                            <th>Importe</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if ($electricityReadings->isEmpty())
                            <tr>
                                <td colspan="7" class="text-center">No hay lecturas registradas.</td>
                            </tr>
                        @else
                            @foreach ($electricityReadings as $reading)
                                <tr>
                                    <td>{{ $reading->reading_date->format('d/m/Y') }}</td>
                                    <td>{{ number_format($reading->initial_reading, 2) }}</td>
                                    <td>{{ number_format($reading->final_reading, 2) }}</td>
                                    <td>{{ number_format($reading->consumption, 2) }}</td>
                                    <td>${{ number_format($reading->kwh_price, 2) }}</td>
                                    <td>${{ number_format($reading->total_amount, 2) }}</td>
                                    <td>
                                        <button wire:click="exportElectricityReading({{ $reading->id }})" 
                                            class="btn btn-sm btn-success" title="Exportar">
                                            <i class="fas fa-file-excel"></i>
                                        </button>
                                        <button wire:click="editReading({{ $reading->id }})" 
                                            class="btn btn-sm btn-primary" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#electricityModal"
                                            title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button onclick="confirmDeleteReading({{ $reading->id }})" 
                                            class="btn btn-sm btn-danger"
                                            title="Eliminar">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>

            <div class="mt-2">
                {{ $electricityReadings->links() }}
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div wire:ignore.self class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="paymentModalLabel">Agregar Pago</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @if (session()->has('warning'))
                        <div class="alert alert-warning">
                            {{ session('warning') }}
                        </div>
                    @endif
                    <form>
                        <div class="mb-3">
                            <label for="amount" class="form-label">Monto</label>
                            <input type="number" class="form-control" id="amount" wire:model="amount">
                            @error('amount')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-primary" wire:click="storePayment">Guardar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Lectura de Luz -->
    <div wire:ignore.self class="modal fade" id="electricityModal" tabindex="-1" aria-labelledby="electricityModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title" id="electricityModalLabel">{{ $isEditMode ? 'Editar' : 'Registrar' }} Lectura de Luz</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form>
                        <div class="mb-3">
                            <label for="reading_date" class="form-label">Fecha de Lectura</label>
                            <input type="date" class="form-control" id="reading_date" wire:model="reading_date">
                            @error('reading_date')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="initial_reading" class="form-label">Lectura Inicial (KWH)</label>
                                <input type="number" step="0.01" class="form-control" id="initial_reading" 
                                    wire:model="initial_reading" wire:change="calculateConsumption">
                                @error('initial_reading')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="final_reading" class="form-label">Lectura Final (KWH)</label>
                                <input type="number" step="0.01" class="form-control" id="final_reading" 
                                    wire:model="final_reading" wire:change="calculateConsumption">
                                @error('final_reading')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="kwh_price" class="form-label">Precio por KWH</label>
                            <input type="number" step="0.01" class="form-control" id="kwh_price" 
                                wire:model="kwh_price" wire:change="calculateConsumption">
                            @error('kwh_price')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="alert alert-info">
                            <p><strong>Consumo:</strong> {{ number_format($consumption, 2) }} KWH</p>
                            <p><strong>Importe Total:</strong> ${{ number_format($electricity_amount, 2) }}</p>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-success" wire:click="storeElectricityReading">{{ $isEditMode ? 'Actualizar' : 'Guardar' }}</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('livewire:init', function() {
        Livewire.on('paymentStored', (data) => {
            let modal = bootstrap.Modal.getInstance(document.getElementById('paymentModal'));
            modal.hide();
            console.log(data);
            setTimeout(() => {
                window.open(data[0].pdfPath, '_blank');
            }, 1000);
        });

        Livewire.on('readingStored', function() {
            let modal = bootstrap.Modal.getInstance(document.getElementById('electricityModal'));
            if (modal) {
                modal.hide();
            }
        });
    });

    function confirmDeleteReading(id) {
        Swal.fire({
            title: '¿Estás seguro?',
            text: "¡No podrás revertir esto!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: '¡Sí, eliminar!',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                @this.call('deleteReading', id);
                Swal.fire(
                    '¡Eliminado!',
                    'La lectura de luz ha sido eliminada.',
                    'success'
                );
            }
        });
    }
</script>
