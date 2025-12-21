<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Merchant extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'business_name',
        'business_email',
        'business_phone',
        'business_address',
        'tax_id',
        'registration_number',
        'status',
        'api_key_live',
        'api_key_test',
        'webhook_url',
        'settings',
    ];

    protected $hidden = [
        'api_key_live',
        'api_key_test',
    ];

    protected $casts = [
        'settings' => 'array',
    ];

    // Relationships

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function webhooks()
    {
        return $this->hasMany(Webhook::class);
    }

    public function fees()
    {
        return $this->hasMany(Fee::class);
    }
}
