<?php

namespace App\Http\Controllers\v1\Sms;

use App\Helpers\Utility;
use App\Http\Controllers\Controller;
use App\Http\Requests\GlobalRequest;
use App\Models\Settings;
use Illuminate\Http\Request;

class SmsController extends Controller
{
    public function estimateCost(GlobalRequest $request): \Illuminate\Http\JsonResponse
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


}
