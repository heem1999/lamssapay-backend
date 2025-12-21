<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiKey extends Model
{
    use HasFactory;

    protected $fillable = [
        'partner_id',
        'key_name',
        'api_key',
        'api_secret_hash',
        'environment',
        'scopes',
        'is_active',
        'last_used_at',
        'expires_at',
        'revoked_at',
    ];

    protected $hidden = [
        'api_secret_hash',
    ];

    protected $casts = [
        'scopes' => 'array',
        'is_active' => 'boolean',
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    // Relationships

    public function partner()
    {
        return $this->belongsTo(Partner::class);
    }
}
