<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LedgerEntry extends Model
{
    protected $fillable = [
        'ledger_id',
        'transaction_id',
        'device_id',
        'card_token',
        'merchant_id',
        'amount',
        'currency',
        'direction',
        'status',
        'auth_code',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];
}
