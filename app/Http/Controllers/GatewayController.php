<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterGatewayRequest;
use App\Http\Requests\UpdateJobResultRequest;
use App\Http\Resources\SmsGatewayResource;
use App\Http\Resources\SmsMessageResource;
use App\Services\GatewayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GatewayController extends Controller
{
    public function __construct(
        protected GatewayService $gatewayService
    ) {}

    /**
     * Register or update a gateway device.
     */
    public function register(RegisterGatewayRequest $request): JsonResponse
    {
        $result = $this->gatewayService->registerGateway($request->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'Gateway registered successfully.',
            'data' => [
                'gateway' => new SmsGatewayResource($result['gateway']),
                'token' => $result['token'],
            ],
        ], 200);
    }

    /**
     * Verify gateway token authentication.
     */
    public function authenticate(Request $request): JsonResponse
    {
        $gateway = $request->get('sms_gateway');

        return response()->json([
            'status' => 'success',
            'message' => 'Gateway authenticated.',
            'data' => [
                'gateway' => new SmsGatewayResource($gateway),
            ],
        ], 200);
    }

    /**
     * Heartbeat update for battery, signal, and online status.
     */
    public function heartbeat(Request $request): JsonResponse
    {
        $gateway = $request->get('sms_gateway');

        $this->gatewayService->updateHeartbeat(
            gateway: $gateway,
            batteryLevel: $request->input('battery_level'),
            signalStrength: $request->input('signal_strength')
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Heartbeat received.',
        ], 200);
    }

    /**
     * Poll the next pending SMS job atomically.
     */
    public function poll(Request $request): JsonResponse
    {
        $gateway = $request->get('sms_gateway');

        $job = $this->gatewayService->getNextPendingJob($gateway);

        if (! $job) {
            return response()->json([
                'status' => 'success',
                'message' => 'No pending jobs.',
                'data' => null,
            ], 200);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Job retrieved.',
            'data' => new SmsMessageResource($job),
        ], 200);
    }

    /**
     * Update job execution result (sent or failed).
     */
    public function updateJobResult(UpdateJobResultRequest $request, int $id): JsonResponse
    {
        $gateway = $request->get('sms_gateway');

        $result = $this->gatewayService->updateJobResult(
            jobId: $id,
            gateway: $gateway,
            status: $request->input('status'),
            errorMessage: $request->input('error_message')
        );

        if (! $result['success']) {
            return response()->json([
                'status' => 'error',
                'message' => $result['message'],
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Job result updated.',
            'data' => new SmsMessageResource($result['job']),
        ], 200);
    }
}
