<?php

namespace App\Http\Controllers\v1\Sms;

use App\Helpers\Utility;
use App\Http\Controllers\Controller;
use App\Http\Requests\GlobalRequest;
use App\Models\Settings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SmsController extends Controller
{
    public function estimateCost(GlobalRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $numbers = $validated['numbers'] ?? [];
        $count = is_array($numbers) ? count($numbers) : 0;
        $sms_price = Settings::get('price', 6);
        $total_price = $sms_price * $count;
        return Utility::outputData(true, "Price estimate successful", [
            'count' => $count,
            'cost' => $total_price
        ], 200);
    }

    public function walletBalance(GlobalRequest $request): JsonResponse
    {
        $user = $request->user();
        $wallet = DB::table('wallets')->where('user_id', $user->id)->first();
        if (!$wallet) {
            return Utility::outputData(false, "Wallet not found", null, 404);
        }

        return Utility::outputData(
            true,
            "Wallet balance retrieved successfully",
            ['balance' => $wallet->amount],
            200
        );
    }
}
