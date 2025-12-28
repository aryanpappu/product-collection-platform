<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MerchantController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $merchants = User::select('id', 'name', 'email', 'created_at')
            ->orderBy('name', 'asc')
            ->get();


        return $this->successResponse($merchants->map(function ($merchant) {
            return [
                'id' => $merchant->id,
                'name' => $merchant->name,
                'email' => $merchant->email,
                'created_at' => $merchant->created_at->toIso8601String(),
            ];
        })->toArray());
    }


}
