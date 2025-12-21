<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Card extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'wallet_id',
        'card_token',
        'token_reference',
        'card_last_four',
        'card_first_six',
        'card_brand',
        'card_type',
        'holder_name',
        'expiry_month',
        'expiry_year',
        'expiry_date',
        'billing_address',
        'billing_zip',
        'billing_country',
        'is_default',
        'is_active',
        'is_verified',
        'verified_at',
        'gateway_provider',
        'gateway_reference',
        'gateway_metadata',
        'daily_limit',
        'transaction_limit',
        'usage_count',
        'last_used_at',
        'total_spent',
        'failed_attempts',
        'locked_until',
        'requires_3ds',
    ];

    protected $hidden = [
        'card_token',
        'holder_name',
        'expiry_month',
        'expiry_year',
        'billing_address',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'is_verified' => 'boolean',
        'requires_3ds' => 'boolean',
        'expiry_date' => 'date',
        'verified_at' => 'datetime',
        'last_used_at' => 'datetime',
        'locked_until' => 'datetime',
        'billing_address' => 'array', // Assuming JSON in DB, cast to array
        'gateway_metadata' => 'array',
    ];

    // Relationships

    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}
