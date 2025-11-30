<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Income extends Model
{
    use HasFactory;

    protected $fillable = [
        'amount',
        'description',
        'photo',
        'cashbox_id'
    ];

    public function cashbox()
    {
        return $this->belongsTo(CashBox::class, 'cashbox_id');
    }
}