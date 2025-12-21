<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Fee extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'type',
        'fixed_amount',
        'percentage_rate',
        'currency',
        'applies_to',
        'partner_id',
        'merchant_id',
        'is_active',
    ];

    protected $casts = [
        'fixed_amount' => 'decimal:2',
        'percentage_rate' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    // Relationships

    public function partner()
    {
        return $this->belongsTo(Partner::class);
    }

    public function merchant()
    {
        return $this->belongsTo(Merchant::class);
    }
}
