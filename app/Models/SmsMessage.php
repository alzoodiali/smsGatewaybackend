<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_app_id',
        'gateway_id',
        'request_id',
        'phone',
        'message',
        'type',
        'status',
        'attempts',
        'max_attempts',
        'locked_at',
        'sent_at',
        'failed_at',
        'error',
    ];

    protected function casts(): array
    {
        return [
            'attempts' => 'integer',
            'max_attempts' => 'integer',
            'locked_at' => 'datetime',
            'sent_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    // ───── Relationships ─────

    public function clientApp(): BelongsTo
    {
        return $this->belongsTo(ClientApp::class, 'client_app_id');
    }

    public function gateway(): BelongsTo
    {
        return $this->belongsTo(SmsGateway::class, 'gateway_id');
    }

    // ───── Status Transitions ─────

    public function markAsProcessing(int $gatewayId): void
    {
        $this->update([
            'status' => 'processing',
            'gateway_id' => $gatewayId,
            'locked_at' => now(),
        ]);
    }

    public function markAsSent(): void
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
            'locked_at' => null,
        ]);
    }

    public function markAsFailed(?string $error = null): void
    {
        $this->update([
            'status' => 'failed',
            'failed_at' => now(),
            'error' => $error,
            'locked_at' => null,
        ]);
    }

    public function resetToPending(): void
    {
        $this->update([
            'status' => 'pending',
            'gateway_id' => null,
            'locked_at' => null,
        ]);
    }

    public function incrementAttempts(): void
    {
        $this->increment('attempts');
    }

    // ───── Helpers ─────

    public function hasExceededMaxAttempts(): bool
    {
        return $this->attempts >= $this->max_attempts;
    }

    public function isStuck(): bool
    {
        if ($this->status !== 'processing' || ! $this->locked_at) {
            return false;
        }

        $timeout = config('otp.gateway.stuck_job_timeout_minutes', 5);

        return $this->locked_at->lt(now()->subMinutes($timeout));
    }

    // ───── Scopes ─────

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    public function scopeStuck($query)
    {
        $timeout = config('otp.gateway.stuck_job_timeout_minutes', 5);

        return $query->where('status', 'processing')
            ->where('locked_at', '<', now()->subMinutes($timeout));
    }
}
