<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CurrencyRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'send_currency_id',
        'receive_currency_id',
        'send_rate',
        'receive_rate',
    ];

    // Определите здесь связи с моделями Currency, если требуется
}
