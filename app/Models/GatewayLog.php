<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GatewayLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'gateway_id',
        'event',
        'payload',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
        ];
    }

    // ───── Relationships ─────

    public function gateway(): BelongsTo
    {
        return $this->belongsTo(SmsGateway::class, 'gateway_id');
    }
}
