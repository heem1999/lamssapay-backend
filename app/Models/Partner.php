<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Partner extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'partner_id',
        'name',
        'email',
        'website',
        'status',
        'tier',
        'webhook_url',
        'callback_url',
        'allowed_scopes',
        'ip_whitelist',
        'rate_limit',
        'is_sandbox',
    ];

    protected $casts = [
        'allowed_scopes' => 'array',
        'ip_whitelist' => 'array',
        'is_sandbox' => 'boolean',
    ];

    // Relationships

    public function apiKeys()
    {
        return $this->hasMany(ApiKey::class);
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
