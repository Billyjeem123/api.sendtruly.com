<?php

namespace App\Helpers;

use App\Models\Settings;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;
use Illuminate\Support\Facades\Auth;


class Utility
{
    public static function outputData($boolean, $message, $data, $statusCode): JsonResponse
    {
        return response()->json([
            'status' => $boolean,
            'message' => $message,
            'data' => $data,
            'status_code' => $statusCode
        ], $statusCode);
    }


    public static function token($length = 6): string
    {
        return str_pad(rand(0, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
    }


    public static function pin(int $length = 4): string
    {
        $max = pow(10, $length) - 1;
        return str_pad((string) random_int(0, $max), $length, '0', STR_PAD_LEFT);
    }



    public static function getExceptionDetails(Throwable $e): array
    {
        // Log the exception details
        Log::error('Exception occurred', [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            #'trace' => $e->getTraceAsString(),
        ]);

        return [
            'line' => $e->getLine(),
            'file' => $e->getFile(),
            'code' => $e->getCode(),
            'message' => $e->getMessage()
        ];
    }



    /**
     * Get the authenticated admin's ID.
     *
     * @return int|null
     */
    public static function getHospitalAdminId(): ?int
    {
        $user = Auth::user();
        if ($user->hasRole('super-admin')) {
            return null;
        }

        return $user ? $user->admin_hospital_id : null;
    }

    public static function getAuthorizedHospitalId()
    {
        $user = Auth::user();
        if ($user->hasRole('super-admin')) {
            return null;
        }
        return $user ? $user->admin_hospital_id : null;
    }





    public static function txRef(string $payment_channel = null, string $provider = null, bool $usePipe = true): string
    {
        $payment_channels = [
            'card' => 'CARD',
            'bank' => 'BANK',
            'bank-transfer' => 'BKTRF',
            'mobile-money' => 'MOBILE',
            'in-app' => 'INAPP',
            "referral" => "REF",
            "bills" => "BILL",
            "betting" => "BET",
            "reverse" => 'REV',
            "virtual" => 'VIRTUAL',
        ];

        $leading = 'BILLIA';
        $time = substr(strval(time()), -4);
        $str = Str::upper(Str::random(4));
        $payment_type = array_key_exists($payment_channel, $payment_channels) ? $payment_channels[$payment_channel] : 'TRNX';

        return sprintf($usePipe ? '%s|%s|%s%s' : '%s-%s-%s%s', $leading, $payment_type, $time, $str);
    }


    /**
     * Get app setting by key.
     */
    public static function getSetting(string $key, $default = null)
    {
        return Settings::get($key, $default);
    }

    // You can also add setSetting() if needed
    public static function setSetting(string $key, $value)
    {
        return Settings::set($key, $value);
    }

    public static function getBankLogoByCode($code)
    {
        $banks = json_decode(file_get_contents(public_path('banks.json')), true);
        foreach ($banks as $bank) {
            if ($bank['code'] === $code) {
                return $bank['logo'];
            }
        }

        return "https://nigerianbanks.xyz/logo/default-image.png";
    }


    public static function getBankLogoByName($searchName)
    {
        $banks = json_decode(file_get_contents(public_path('banks.json')), true);
        $searchName = strtolower($searchName);

        foreach ($banks as $bank) {
            $bankName = strtolower($bank['name'] ?? '');

            // Split the bank name into words and check if any match the start of the search
            $bankWords = explode(' ', $bankName);
            if (isset($bankWords[0]) && strpos($searchName, $bankWords[0]) !== false) {
                return $bank['logo'];
            }

            // Optional: check if the searchName contains the first bank word
            if (strpos($bankName, $searchName) !== false) {
                return $bank['logo'];
            }
        }

        // Default image if no match found
        return "https://nigerianbanks.xyz/logo/default-image.png";
    }




}
