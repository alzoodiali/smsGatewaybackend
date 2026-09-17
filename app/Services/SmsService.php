<?php

namespace App\Services;

use Illuminate\Support\Str;

class SmsService
{
    /**
     * Clean and normalize a Yemeni phone number to format: 967XXXXXXXXX
     *
     * Supports:
     *  - 771234567
     *  - 0771234567
     *  - +967771234567
     *  - 00967771234567
     *  - 967771234567
     *  - 77 123 4567 (with spaces)
     *  - 77-123-4567 (with dashes)
     */
    public static function cleanYemeniNumber(string $phone): ?string
    {
        // Remove spaces, dashes, parentheses
        $phone = preg_replace('/[\s\-\(\)]+/', '', $phone);

        // Remove leading +
        $phone = ltrim($phone, '+');

        // Remove leading 00 (international prefix)
        if (str_starts_with($phone, '00967')) {
            $phone = substr($phone, 2); // Remove '00', keep '967...'
        }

        // Remove leading 0 (local format like 0771234567)
        if (str_starts_with($phone, '0') && ! str_starts_with($phone, '00')) {
            $phone = '967' . substr($phone, 1);
        }

        // If it doesn't start with 967, add it
        if (! str_starts_with($phone, '967')) {
            $phone = '967' . $phone;
        }

        // Validate: must be exactly 12 digits (967 + 9 digits)
        if (! preg_match('/^967\d{9}$/', $phone)) {
            return null;
        }

        // Validate prefix (7X after 967)
        $localPart = substr($phone, 3, 2); // e.g., '77', '73', etc.
        $supportedPrefixes = config('sms.supported_prefixes', ['70', '71', '73', '77', '78']);

        if (! in_array($localPart, $supportedPrefixes)) {
            return null;
        }

        return $phone;
    }

    /**
     * Validate if a given number is a valid Yemeni phone number.
     */
    public static function isValidYemeniNumber(string $phone): bool
    {
        return self::cleanYemeniNumber($phone) !== null;
    }

    /**
     * Generate a unique request ID for SMS messages.
     */
    public static function generateRequestId(string $prefix = 'SMS'): string
    {
        return $prefix . '-' . now()->format('Ymd-His') . '-' . Str::upper(Str::random(6));
    }

    /**
     * Format OTP message using configured template.
     */
    public static function formatOtpMessage(string $code): string
    {
        $template = config('sms.message_template', 'رمز التحقق الخاص بك هو: :code');

        return str_replace(':code', $code, $template);
    }
}
