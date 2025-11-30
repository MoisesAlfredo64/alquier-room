<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashBox extends Model
{
    use HasFactory;

    protected $fillable = [
        'spent',
        'closing_date',
        'status',
        'user_id'
    ];

    // Relación con pagos (ingresos)
    public function payments()
    {
        return $this->hasMany(Payment::class, 'cashbox_id');
    }

    // Relación con gastos (egresos)
    public function expenses()
    {
        return $this->hasMany(Expense::class, 'cashbox_id');
    }

    // Relación con ingresos extra
    public function incomes()
    {
        return $this->hasMany(Income::class, 'cashbox_id');
    }

    // Relación con usuario
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
