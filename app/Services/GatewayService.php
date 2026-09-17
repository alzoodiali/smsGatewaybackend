<?php

namespace App\Services;

use App\Models\GatewayLog;
use App\Models\SmsGateway;
use App\Models\SmsMessage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class GatewayService
{
    /**
     * Register a new gateway device.
     */
    public function registerGateway(array $data): array
    {
        $deviceId = $data['device_id'];
        $name = $data['name'];
        $phoneNumber = $data['phone_number'] ?? null;
        $simSlot = $data['sim_slot'] ?? 1;

        $existing = SmsGateway::where('device_id', $deviceId)->first();

        if ($existing) {
            $plainToken = 'gw_' . Str::random(60);
            $existing->update([
                'name' => $name,
                'token_hash' => Hash::make($plainToken),
                'phone_number' => $phoneNumber ?? $existing->phone_number,
                'sim_slot' => $simSlot,
                'status' => 'online',
                'last_ping' => now(),
            ]);

            return [
                'gateway' => $existing,
                'token' => $plainToken,
            ];
        }

        $plainToken = 'gw_' . Str::random(60);

        $gateway = SmsGateway::create([
            'name' => $name,
            'device_id' => $deviceId,
            'token_hash' => Hash::make($plainToken),
            'status' => 'online',
            'phone_number' => $phoneNumber,
            'sim_slot' => $simSlot,
            'last_ping' => now(),
        ]);

        $this->logEvent($gateway, 'registered', ['ip' => request()->ip()]);

        return [
            'gateway' => $gateway,
            'token' => $plainToken,
        ];
    }

    /**
     * Update gateway heartbeat.
     */
    public function updateHeartbeat(SmsGateway $gateway, ?int $batteryLevel = null, ?int $signalStrength = null): void
    {
        $gateway->update([
            'status' => 'online',
            'battery_level' => $batteryLevel ?? $gateway->battery_level,
            'signal_strength' => $signalStrength ?? $gateway->signal_strength,
            'last_ping' => now(),
        ]);

        $this->logEvent($gateway, 'heartbeat');
    }

    /**
     * Atomically claim next pending SMS job.
     */
    public function getNextPendingJob(SmsGateway $gateway): ?SmsMessage
    {
        return DB::transaction(function () use ($gateway) {
            $job = SmsMessage::where('status', 'pending')
                ->lockForUpdate()
                ->oldest()
                ->first();

            if (! $job) {
                return null;
            }

            $job->update([
                'status' => 'processing',
                'gateway_id' => $gateway->id,
                'attempts' => $job->attempts + 1,
            ]);

            $this->logEvent($gateway, 'job_claimed', [
                'job_id' => $job->id,
                'phone' => $job->phone_number,
            ]);

            return $job;
        });
    }

    /**
     * Update job result.
     */
    public function updateJobResult(int $jobId, SmsGateway $gateway, string $status, ?string $errorMessage = null): array
    {
        $job = SmsMessage::find($jobId);

        if (! $job) {
            return [
                'success' => false,
                'message' => 'Job not found.',
                'job' => null,
            ];
        }

        $job->update([
            'status' => $status,
            'error_message' => $errorMessage,
            'sent_at' => $status === 'sent' ? now() : null,
            'gateway_id' => $gateway->id,
        ]);

        $this->logEvent($gateway, 'job_updated', [
            'job_id' => $job->id,
            'status' => $status,
            'error' => $errorMessage,
        ]);

        return [
            'success' => true,
            'message' => 'Job result updated successfully.',
            'job' => $job,
        ];
    }

    /**
     * Log a gateway event.
     */
    public function logEvent(SmsGateway $gateway, string $event, array $payload = []): void
    {
        GatewayLog::create([
            'gateway_id' => $gateway->id,
            'event' => $event,
            'payload' => ! empty($payload) ? $payload : null,
        ]);
    }
}
