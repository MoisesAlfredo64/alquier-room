<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ElectricityReading extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'rent_id',
        'reading_date',
        'initial_reading',
        'final_reading',
        'consumption',
        'kwh_price',
        'total_amount',
    ];

    protected $casts = [
        'reading_date' => 'date',
    ];

    // Relación con Client
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    // Relación con Rent
    public function rent()
    {
        return $this->belongsTo(Rent::class);
    }
}
