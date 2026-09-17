<?php

namespace App\Http\Controllers;

use App\Http\Requests\ConnectClientRequest;
use App\Http\Requests\SendOtpRequest;
use App\Http\Requests\VerifyOtpRequest;
use App\Models\ClientApp;
use App\Services\OtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class ClientController extends Controller
{
    public function __construct(
        protected OtpService $otpService
    ) {}

    /**
     * Silent Pairing endpoint for client app.
     */
    public function connect(ConnectClientRequest $request): JsonResponse
    {
        $appName = $request->input('app_name', $request->input('name', 'Client App'));
        $appId = $request->input('app_id', Str::uuid()->toString());

        $clientApp = ClientApp::where('app_id', $appId)->first();

        if (! $clientApp) {
            $clientApp = ClientApp::create([
                'name' => $appName,
                'app_id' => $appId,
                'status' => 'active',
                'api_key_hash' => 'temp',
                'api_key_prefix' => 'temp',
            ]);
        } else {
            $clientApp->update([
                'name' => $appName,
                'status' => 'active',
            ]);
        }

        $apiKey = ClientApp::generateApiKey($clientApp);

        return response()->json([
            'status' => 'success',
            'message' => 'Client connected successfully.',
            'data' => [
                'app_id' => $clientApp->app_id,
                'app_name' => $clientApp->name,
                'api_key' => $apiKey,
            ],
        ], 200);
    }

    /**
     * Request OTP code.
     */
    public function sendOtp(SendOtpRequest $request): JsonResponse
    {
        $clientApp = $request->get('client_app');

        $result = $this->otpService->sendOtp(
            phone: $request->input('phone_number'),
            clientAppId: $clientApp?->id,
            purpose: $request->input('purpose', 'authentication')
        );

        return response()->json([
            'status' => 'success',
            'message' => 'OTP sent successfully.',
            'data' => [
                'request_id' => $result['request_id'],
                'expires_at' => $result['expires_at'],
            ],
        ], 200);
    }

    /**
     * Verify OTP code.
     */
    public function verifyOtp(VerifyOtpRequest $request): JsonResponse
    {
        $success = $this->otpService->verifyOtp(
            phone: $request->input('phone_number'),
            code: $request->input('code')
        );

        if (! $success) {
            return response()->json([
                'status' => 'error',
                'message' => 'رمز OTP غير صحيح أو منتهي الصلاحية.',
            ], 422);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'تم التحقق من رمز OTP بنجاح.',
        ], 200);
    }
}
