<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebhookDelivery extends Model
{
    use HasFactory;

    protected $fillable = [
        'webhook_id',
        'event_type',
        'event_id',
        'payload',
        'status_code',
        'response_body',
        'success',
        'attempt',
        'sent_at',
        'next_retry_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'success' => 'boolean',
        'sent_at' => 'datetime',
        'next_retry_at' => 'datetime',
    ];

    // Relationships

    public function webhook()
    {
        return $this->belongsTo(Webhook::class);
    }
}
