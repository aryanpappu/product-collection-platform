<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMerchantContext
{
    /**
     * Handle an incoming request.
     *
     * @param Closure(Request): (Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get merchant_id from X-Merchant-ID header
        $merchantId = $request->header('X-Merchant-ID');

        if (!$merchantId) {
            return response()->json([
                'error' => 'Merchant ID is required',
                'message' => 'Please provide X-Merchant-ID header',
            ], 401);
        }

        $merchant = User::find($merchantId);

        if (!$merchant) {
            return response()->json([
                'error' => 'Invalid merchant',
                'message' => 'Merchant not found',
            ], 404);
        }
        $request->attributes->set('merchant_id', $merchantId);
        $request->attributes->set('merchant', $merchant);

        return $next($request);
    }
}
