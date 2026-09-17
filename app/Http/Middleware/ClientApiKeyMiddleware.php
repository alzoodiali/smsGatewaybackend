<?php

namespace App\Http\Middleware;

use App\Models\ClientApp;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ClientApiKeyMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('X-API-KEY');

        if (! $apiKey) {
            return response()->json([
                'success' => false,
                'message' => 'مفتاح API مطلوب.',
            ], 401);
        }

        // Quick prefix lookup to avoid checking all records
        $prefix = substr($apiKey, 0, 8);
        $candidates = ClientApp::where('api_key_prefix', $prefix)->get();

        $clientApp = null;
        foreach ($candidates as $candidate) {
            if ($candidate->verifyApiKey($apiKey)) {
                $clientApp = $candidate;
                break;
            }
        }

        if (! $clientApp) {
            return response()->json([
                'success' => false,
                'message' => 'مفتاح API غير صالح.',
            ], 401);
        }

        if (! $clientApp->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'تطبيق العميل معطل أو ملغي.',
            ], 403);
        }

        // Update last used timestamp (throttled to avoid excessive writes)
        if (! $clientApp->last_used_at || $clientApp->last_used_at->lt(now()->subMinutes(5))) {
            $clientApp->markLastUsed();
        }

        // Attach to request for downstream use
        $request->merge(['client_app' => $clientApp]);
        $request->attributes->set('client_app', $clientApp);

        return $next($request);
    }
}
