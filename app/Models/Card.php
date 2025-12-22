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
    ];

    protected $hidden = [
        'token_reference',
        'fingerprint',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function device()
    {
        return $this->belongsTo(Device::class);
    }
}
