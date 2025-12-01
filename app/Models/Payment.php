<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'amount',
        'payment_date',
        'warranty_amount',
        'rent_id',
        'cashbox_id',
    ];

    public function rent()
    {
        return $this->belongsTo(Rent::class);
    }

    public function cashbox()
    {
        return $this->belongsTo(CashBox::class);
    }
}
