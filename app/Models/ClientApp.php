<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ClientApp extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'app_id',
        'api_key_hash',
        'api_key_prefix',
        'status',
        'description',
        'rate_limit_per_minute',
        'last_used_at',
    ];

    protected $hidden = [
        'api_key_hash',
    ];

    protected function casts(): array
    {
        return [
            'last_used_at' => 'datetime',
            'rate_limit_per_minute' => 'integer',
        ];
    }

    // ───── Relationships ─────

    public function smsMessages(): HasMany
    {
        return $this->hasMany(SmsMessage::class, 'client_app_id');
    }

    public function otpCodes(): HasMany
    {
        return $this->hasMany(OtpCode::class, 'client_app_id');
    }

    // ───── API Key Management ─────

    /**
     * Generate a new API key and store its hash.
     * Returns the plain text key (only shown once).
     */
    public static function generateApiKey(self $app): string
    {
        $plainKey = 'sms_' . Str::random(40);
        $app->update([
            'api_key_hash' => Hash::make($plainKey),
            'api_key_prefix' => substr($plainKey, 0, 8),
        ]);

        return $plainKey;
    }

    /**
     * Verify a plain text API key against the stored hash.
     */
    public function verifyApiKey(string $plainKey): bool
    {
        return Hash::check($plainKey, $this->api_key_hash);
    }

    // ───── Status Helpers ─────

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function markLastUsed(): void
    {
        $this->update(['last_used_at' => now()]);
    }

    // ───── Scopes ─────

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
