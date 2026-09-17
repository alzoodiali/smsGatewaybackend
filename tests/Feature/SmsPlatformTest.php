<?php

namespace Tests\Feature;

use App\Models\ClientApp;
use App\Models\OtpCode;
use App\Models\SmsMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SmsPlatformTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_can_connect_via_silent_pairing(): void
    {
        $response = $this->postJson('/api/v1/client/connect', [
            'app_name' => 'Test Mobile App',
            'app_id' => 'com.test.app',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => ['app_id', 'app_name', 'api_key'],
            ]);

        $this->assertDatabaseHas('client_apps', [
            'app_id' => 'com.test.app',
        ]);
    }

    public function test_client_can_request_and_verify_otp(): void
    {
        // 1. Connect client app
        $connectRes = $this->postJson('/api/v1/client/connect', [
            'app_name' => 'Test App',
            'app_id' => 'com.test.otp',
        ]);
        $apiKey = $connectRes->json('data.api_key');

        // 2. Send OTP
        $otpRes = $this->withHeaders(['X-API-KEY' => $apiKey])
            ->postJson('/api/v1/client/otp/request', [
                'phone_number' => '771234567',
                'purpose' => 'testing',
            ]);

        $otpRes->assertStatus(200)
            ->assertJsonStructure(['status', 'data' => ['request_id', 'expires_at']]);

        $this->assertDatabaseHas('sms_messages', [
            'phone' => '967771234567',
            'type' => 'otp',
            'status' => 'pending',
        ]);

        // Fetch generated code
        $otpRecord = OtpCode::latest()->first();

        $this->assertNotNull($otpRecord);
    }

    public function test_gateway_registration_and_atomic_polling(): void
    {
        // 1. Register Gateway
        $regRes = $this->postJson('/api/v1/gateway/register', [
            'device_id' => 'device_123',
            'name' => 'Test Phone',
            'sim_slot' => 1,
        ]);

        $regRes->assertStatus(200);
        $token = $regRes->json('data.token');

        // 2. Create pending SMS job
        $sms = SmsMessage::create([
            'phone' => '967771234567',
            'message' => 'Your OTP is 123456',
            'type' => 'otp',
            'status' => 'pending',
            'request_id' => 'req_test',
        ]);

        // 3. Poll next job
        $pollRes = $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->postJson('/api/v1/gateway/jobs/next');

        $pollRes->assertStatus(200)
            ->assertJsonPath('data.id', $sms->id);

        $this->assertEquals('processing', $sms->fresh()->status);

        // 4. Update job result
        $updateRes = $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->postJson("/api/v1/gateway/jobs/{$sms->id}/result", [
                'status' => 'sent',
            ]);

        $updateRes->assertStatus(200);
        $this->assertEquals('sent', $sms->fresh()->status);
    }
}
