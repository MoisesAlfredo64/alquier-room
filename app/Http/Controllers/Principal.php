<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Rent;
use App\Models\Room;
use Illuminate\Support\Facades\DB;

class Principal extends Controller
{
    public function index()
    {
        return view('auth.login');
    }

    public function dashboard()
    {
        $rents = DB::table('rents')
            ->select(DB::raw('MONTH(created_at) as month'), DB::raw('COUNT(*) as count'))
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('count', 'month');

        $payments = DB::table('payments')
            ->select(DB::raw('MONTH(created_at) as month'), DB::raw('SUM(amount) as total'))
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total', 'month');

        $monthNames = [
            1 => 'Ene',
            2 => 'Feb',
            3 => 'Mar',
            4 => 'Abr',
            5 => 'May',
            6 => 'Jun',
            7 => 'Jul',
            8 => 'Ago',
            9 => 'Sep',
            10 => 'Oct',
            11 => 'Nov',
            12 => 'Dic'
        ];

        // Normalizar los datos para Chart.js
        $months = range(1, 12);
        $rentCounts = [];
        $paymentTotals = [];
        $labels = [];

        foreach ($months as $month) {
            $rentCounts[] = $rents->get($month, 0);
            $paymentTotals[] = $payments->get($month, 0);
            $labels[] = $monthNames[$month];
        }

        $data = [
            // Mostrar solo las habitaciones ocupadas (rentas activas)
            'totalRent' => Rent::where('status', 1)->count(),
            'totalRoom' => Room::count(),
            'totalClient' => Client::count(),
            'rentCounts' => $rentCounts,
            'paymentTotals' => $paymentTotals,
            'months' => $labels
        ];

        // Habitaciones con pagos próximos a vencer (7 días antes) o ya vencidos
        $today = now();
        $sevenDaysLater = $today->copy()->addDays(7);
        $proximosPagos = [];
        $rentsWithRoom = Rent::with(['room', 'client', 'payments'])
            ->where('status', 1)
            ->get();

        foreach ($rentsWithRoom as $rent) {
            // Obtener el último pago realizado
            $lastPayment = $rent->payments->sortByDesc('created_at')->first();
            // Si no hay pagos, usar la fecha de creación del alquiler
            $lastDate = $lastPayment ? $lastPayment->created_at : $rent->created_at;
            // Calcular la próxima fecha de vencimiento (1 mes después del último pago)
            $nextDueDate = \Carbon\Carbon::parse($lastDate)->addMonth();
            
            // Mostrar si:
            // 1. Ya venció (está en el pasado)
            // 2. Está dentro de los próximos 7 días
            if ($nextDueDate->isPast() || $nextDueDate->between($today, $sevenDaysLater)) {
                $proximosPagos[] = [
                    'room_number' => $rent->room->number,
                    'client_name' => $rent->client->full_name,
                    'rental_price' => $rent->room->rentalprice,
                    'due_date' => $nextDueDate->format('d/m/Y'),
                    'is_overdue' => $nextDueDate->isPast() // Indicador si ya venció
                ];
            }
        }

        return view('admin.dashboard', compact('data', 'proximosPagos'));
    }
}
