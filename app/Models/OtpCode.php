<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Hash;

class OtpCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_app_id',
        'phone',
        'code_hash',
        'request_id',
        'expires_at',
        'verified_at',
        'attempts',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'verified_at' => 'datetime',
            'attempts' => 'integer',
        ];
    }

    // ───── Relationships ─────

    public function clientApp(): BelongsTo
    {
        return $this->belongsTo(ClientApp::class, 'client_app_id');
    }

    // ───── Verification Logic ─────

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }

    public function isValid(): bool
    {
        return ! $this->isExpired() && ! $this->isVerified();
    }

    public function hasExceededAttempts(): bool
    {
        return $this->attempts >= config('otp.max_verify_attempts', 3);
    }

    /**
     * Verify the OTP code. Increments attempts on each call.
     * Returns true only if code matches, is valid, and under max attempts.
     */
    public function verifyCode(string $code): bool
    {
        if (! $this->isValid()) {
            return false;
        }

        if ($this->hasExceededAttempts()) {
            return false;
        }

        $this->increment('attempts');

        if (! Hash::check($code, $this->code_hash)) {
            return false;
        }

        $this->update(['verified_at' => now()]);

        return true;
    }

    // ───── Scopes ─────

    public function scopeActive($query)
    {
        return $query->whereNull('verified_at')
            ->where('expires_at', '>', now());
    }

    public function scopeForPhone($query, string $phone)
    {
        return $query->where('phone', $phone);
    }
}
