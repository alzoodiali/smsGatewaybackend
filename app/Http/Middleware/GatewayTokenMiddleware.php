<?php

namespace App\Http\Middleware;

use App\Models\SmsGateway;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class GatewayTokenMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $deviceId = $request->header('X-Gateway-ID') ?? $request->input('device_id');
        $token = $request->header('X-Gateway-Token') ?? $request->input('token') ?? $request->bearerToken();

        if (! $token) {
            return response()->json([
                'success' => false,
                'message' => 'رمز مصادقة الـ Gateway مطلوب.',
            ], 401);
        }

        $gateway = null;

        if ($deviceId) {
            $gateway = SmsGateway::where('device_id', $deviceId)->first();
            if ($gateway && ! $gateway->verifyToken($token)) {
                $gateway = null;
            }
        } else {
            // Find gateway matching token
            $allGateways = SmsGateway::all();
            foreach ($allGateways as $gw) {
                if ($gw->verifyToken($token)) {
                    $gateway = $gw;
                    break;
                }
            }
        }

        if (! $gateway) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات مصادقة الـ Gateway غير صحيحة.',
            ], 401);
        }

        if ($gateway->status === 'disabled') {
            return response()->json([
                'success' => false,
                'message' => 'الـ Gateway معطل.',
            ], 403);
        }

        $request->merge(['sms_gateway' => $gateway]);
        $request->attributes->set('sms_gateway', $gateway);

        return $next($request);
    }
}
