<?php

namespace App\Services;

use App\Models\OtpCode;
use App\Models\SmsMessage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class OtpService
{
    /**
     * Send OTP to a phone number.
     * Creates OTP record and SMS job.
     */
    public function sendOtp(string $phone, ?int $clientAppId = null, ?string $purpose = 'authentication'): array
    {
        $cleanPhone = SmsService::cleanYemeniNumber($phone);

        if (! $cleanPhone) {
            throw ValidationException::withMessages([
                'phone' => ['رقم الهاتف غير صحيح. يجب أن يكون رقم يمني يبدأ بـ 77, 73, 71, 70, أو 78'],
            ]);
        }

        $this->checkRateLimit($cleanPhone);
        $this->checkCooldown($cleanPhone);

        $otp = $this->generateOtp();
        $requestId = SmsService::generateRequestId('OTP');
        $message = SmsService::formatOtpMessage($otp);

        return DB::transaction(function () use ($cleanPhone, $otp, $requestId, $message, $clientAppId) {
            $otpCode = OtpCode::create([
                'client_app_id' => $clientAppId,
                'phone' => $cleanPhone,
                'code_hash' => Hash::make($otp),
                'request_id' => $requestId,
                'expires_at' => now()->addMinutes(config('otp.expires_minutes', 5)),
                'attempts' => 0,
            ]);

            $smsMessage = SmsMessage::create([
                'client_app_id' => $clientAppId,
                'request_id' => $requestId,
                'phone' => $cleanPhone,
                'message' => $message,
                'type' => 'otp',
                'status' => 'pending',
                'attempts' => 0,
                'max_attempts' => config('otp.gateway.max_retry_attempts', 3),
            ]);

            return [
                'request_id' => $requestId,
                'expires_at' => $otpCode->expires_at,
                'expires_in' => config('otp.expires_minutes', 5) * 60,
            ];
        });
    }

    /**
     * Verify OTP code for a phone number.
     */
    public function verifyOtp(string $phone, string $code): bool
    {
        $cleanPhone = SmsService::cleanYemeniNumber($phone);

        if (! $cleanPhone) {
            return false;
        }

        $otpCode = OtpCode::where('phone', $cleanPhone)
            ->whereNull('verified_at')
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if (! $otpCode) {
            return false;
        }

        if (Hash::check($code, $otpCode->code_hash)) {
            $otpCode->update(['verified_at' => now()]);
            return true;
        }

        $otpCode->increment('attempts');
        return false;
    }

    /**
     * Generate a secure OTP code.
     */
    private function generateOtp(): string
    {
        $length = config('otp.length', 6);
        $max = (int) str_repeat('9', $length);

        return str_pad((string) random_int(0, $max), $length, '0', STR_PAD_LEFT);
    }

    /**
     * Check rate limit per phone and per IP.
     */
    private function checkRateLimit(string $phone): void
    {
        $config = config('otp.rate_limit');

        // Per-phone rate limit
        $phoneKey = 'otp:phone:' . $phone;
        if (RateLimiter::tooManyAttempts($phoneKey, $config['per_phone_max'] ?? 5)) {
            $seconds = RateLimiter::availableIn($phoneKey);
            throw ValidationException::withMessages([
                'phone' => ["تم تجاوز الحد الأقصى لطلبات OTP. حاول مرة أخرى بعد {$seconds} ثانية."],
            ]);
        }
        RateLimiter::hit($phoneKey, ($config['per_phone_window_minutes'] ?? 60) * 60);

        // Per-IP rate limit
        $ipKey = 'otp:ip:' . request()->ip();
        if (RateLimiter::tooManyAttempts($ipKey, $config['per_ip_max'] ?? 20)) {
            $seconds = RateLimiter::availableIn($ipKey);
            throw ValidationException::withMessages([
                'phone' => ["تم تجاوز الحد الأقصى من هذا الجهاز. حاول مرة أخرى بعد {$seconds} ثانية."],
            ]);
        }
        RateLimiter::hit($ipKey, ($config['per_ip_window_minutes'] ?? 60) * 60);
    }

    /**
     * Prevent rapid-fire OTP requests (cooldown period).
     */
    private function checkCooldown(string $phone): void
    {
        $cooldown = config('otp.rate_limit.cooldown_seconds', 60);

        $lastOtp = OtpCode::where('phone', $phone)
            ->latest()
            ->first();

        if ($lastOtp && $lastOtp->created_at->gt(now()->subSeconds($cooldown))) {
            $remaining = $cooldown - now()->diffInSeconds($lastOtp->created_at);
            throw ValidationException::withMessages([
                'phone' => ["يرجى الانتظار {$remaining} ثانية قبل طلب رمز جديد."],
            ]);
        }
    }
}
