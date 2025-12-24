<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Card extends Model
{
    use HasFactory;

    protected $fillable = [
        'device_id',
        'token_reference',
        'masked_pan',
        'scheme',
        'card_art',
        'is_default',
        'fingerprint',
        'status',
        'issuer_reference',
        'merchant_status',
        'is_settlement_default',
    ];

    protected $hidden = [
        // 'token_reference', // Exposed for MVP NFC Simulation
        'fingerprint',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_settlement_default' => 'boolean',
    ];

    public function device()
    {
        return $this->belongsTo(Device::class);
    }
}
