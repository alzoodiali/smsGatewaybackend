<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Hash;

class SmsGateway extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'device_id',
        'token_hash',
        'status',
        'sim_slot',
        'phone_number',
        'last_seen_at',
        'connected_at',
        'disconnected_at',
    ];

    protected $hidden = [
        'token_hash',
    ];

    protected function casts(): array
    {
        return [
            'sim_slot' => 'integer',
            'last_seen_at' => 'datetime',
            'connected_at' => 'datetime',
            'disconnected_at' => 'datetime',
        ];
    }

    // ───── Relationships ─────

    public function smsMessages(): HasMany
    {
        return $this->hasMany(SmsMessage::class, 'gateway_id');
    }

    public function gatewayLogs(): HasMany
    {
        return $this->hasMany(GatewayLog::class, 'gateway_id');
    }

    // ───── Token Verification (Hash-based) ─────

    public function verifyToken(string $plainToken): bool
    {
        return Hash::check($plainToken, $this->token_hash);
    }

    // ───── Status Management ─────

    public function markOnline(): void
    {
        $this->update([
            'status' => 'online',
            'last_seen_at' => now(),
            'connected_at' => $this->connected_at ?? now(),
            'disconnected_at' => null,
        ]);
    }

    public function markOffline(): void
    {
        $this->update([
            'status' => 'offline',
            'disconnected_at' => now(),
        ]);
    }

    public function isAvailable(): bool
    {
        if ($this->status === 'disabled') {
            return false;
        }

        $timeout = config('otp.gateway.heartbeat_timeout_seconds', 120);

        if ($this->last_seen_at && $this->last_seen_at->lt(now()->subSeconds($timeout))) {
            return false;
        }

        return in_array($this->status, ['online', 'busy']);
    }

    // ───── Scopes ─────

    public function scopeOnline($query)
    {
        return $query->where('status', 'online');
    }

    public function scopeAvailable($query)
    {
        $timeout = config('otp.gateway.heartbeat_timeout_seconds', 120);

        return $query->whereIn('status', ['online', 'busy'])
            ->where(function ($q) use ($timeout) {
                $q->whereNull('last_seen_at')
                  ->orWhere('last_seen_at', '>=', now()->subSeconds($timeout));
            });
    }
}
