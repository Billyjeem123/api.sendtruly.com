<?php

namespace App\Http\Controllers\v1\Sms;

use App\Helpers\Utility;
use App\Http\Controllers\Controller;
use App\Http\Requests\SmsRequest;
use App\Models\Settings;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class SmsController extends Controller
{
    private $smsConfig = [
        'url' => 'https://uqsng.breelink.com:1443/cgi-bin/sendsms',
        'username' => 'sendtruly',
        'password' => 'HoQgzY',
        'default_sender' => 'SENDTRULY'
    ];


    /**
     * Process sending SMS based on the preferred SMS provider.
     * @throws ValidationException
     */
    public function processSMS(SmsRequest $request): JsonResponse
    {
        $preferred_sms = Settings::get('preferred_sms', "intervas");

        switch ($preferred_sms) {
            case 'intervas':
                return $this->processIntervasSMS($request);
            default:
                return response()->json([
                    'status' => false,
                    'message' => 'SMS provider not supported.',
                ], 400);
        }
    }

    public function processIntervasSMS(SmsRequest $request): JsonResponse
    {
        $validated = $request->validated();

        try {
            DB::beginTransaction();

            $recipients = $this->getRecipients($validated['numbers']);
            if (empty($recipients)) {
                throw ValidationException::withMessages([
                    'recipients' => ['No valid recipients found.']
                ]);
            }
            $count = is_array($validated['numbers']) ? count($validated['numbers']) : 0;
            $sms_price = Settings::get('price', 6);
            $sms_cost = $sms_price * $count;

            $user = $request->user();
            $currentBalance = DB::table('wallets')->where('user_id', $user->id)->value('amount');
            if ($currentBalance < $sms_cost) {
                throw ValidationException::withMessages([
                    'wallet' => ['Insufficient funds. Kindly fund wallet to continue.']
                ]);
            }

            $deliveryResults = $this->sendToAllRecipients($validated, $recipients);
//             $deliveryResults = $this->smsResponse(); // test line?

            if ($deliveryResults['success_count'] > 0) {
                $this->saveTransactions($user, 'debit', $sms_cost, $currentBalance);
                $smsMessage = $this->createSmsMessage($validated, $recipients, $deliveryResults, $sms_cost);
                $this->saveDeliveryRecords($smsMessage, $deliveryResults['successful_sends']);

                DB::commit();

                return response()->json([
                    'status' => true,
                    'message' => "SMS sent successfully to {$deliveryResults['success_count']} recipients.",
                    'data' => [
                        'successful' => $deliveryResults['success_count'],
                        'failed' => $deliveryResults['failed_count'] ?? 0,
                    ]
                ], 200);
            }

            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => "SMS failed to send to all recipients.",
            ], 422);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('SMS Send Error: ' . $e->getMessage());

            throw ValidationException::withMessages([
                'sms' => ['SMS failed due to a system error. Please try again later.']
            ]);
        }
    }



    public function smsResponse()
    {
        return ([
            'successful_sends' => [
                [
                    'recipient' => '08117283226',
                    'provider_id' => 'e9894749-9e48-4a33-9700-7fff0f46af82',
                    'gateway_response' => 'e9894749-9e48-4a33-9700-7fff0f46af82',
                    'credits' => 1
                ]
            ],
            'success_count' => 1,
            'total_credits' => 1
        ]);
    }

    private function sendToAllRecipients($validated, $recipients): array
    {
        $successfulSends = [];
        $successCount = 0;
        $totalCredits = 0;

        foreach ($recipients as $recipient) {
            $response = $this->sendSmsToGateway($validated, $recipient);

            if ($response['success']) {
                $credits = $this->calculateCredits($validated['message']);

                $successfulSends[] = [
                    'recipient' => $recipient,
                    'provider_id' => $response['provider_id'], # ID from provider
                    'gateway_response' => $response['response'],
                    'credits' => $credits
                ];

                $successCount++;
                $totalCredits += $credits;

                # Small delay to prevent overwhelming gateway
                usleep(100000); # 0.1s
            } else {
                Log::error("SMS failed for {$recipient}: " . $response['error']);
            }
        }

        return [
            'successful_sends' => $successfulSends,
            'success_count' => $successCount,
            'total_credits' => $totalCredits
        ];
    }


    private function createSmsMessage($validated, $recipients, $deliveryResults, $sms_cost)
    {
        $status = $this->determineMessageStatus(
            $deliveryResults['success_count'],
            count($recipients)
        );

        $now = now();

        $smsId = DB::table('sms')->insertGetId([
            'user_id'         => Auth::id(),
            'message_type'    => "Transactional",
            'sender_id'       => $validated['sender_id'],
            'campaign_name'   => $validated['campaign_name'],
            'message_content' => $validated['message'],
            'is_draft'        => false,
            'is_auto_message' => $validated['auto_message'] ?? false,
            'channel'         => 'Transactional',
            'interface'       => 'api',
            'sms_type'        => $this->detectSmsType($validated['message']),
            'status'          => $status,
            'credit_used'     => $sms_cost,
            'created_at'      => $now,
            'updated_at'      => $now,
        ]);

        return DB::table('sms')->where('id', $smsId)->first(); // returns stdClass
    }


    private function saveDeliveryRecords( $deliverable, array $successfulSends)
    {
        $deliveryRecords = [];
        foreach ($successfulSends as $send) {
            $deliveryRecords[] = [
                'sms_message_id' => $deliverable->id,
                'deliverable_id'  => $deliverable->id,
                'deliverable_type' => 'App\Models\SMS',
                'recipient' => $send['recipient'],
                'provider_message_id' => $send['provider_id'],
                'success' => 'Message sent, waiting for delivery report',
                'gateway_response' => $send['gateway_response'],
                'credits_used' => $send['credits'],
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        if (!empty($deliveryRecords)) {
            DB::table('sms_deliveries')->insert($deliveryRecords);
        }
    }


    private function determineMessageStatus($successCount, $totalRecipients): string
    {
        if ($successCount === 0) {
            return 'failed';
        } elseif ($successCount === $totalRecipients) {
            return 'sent';
        } else {
            return 'partial';
        }
    }

    private function getRecipients($recipientNumbers): array
    {
        $recipients = [];

        # 1. From manually entered numbers
        if ($recipientNumbers) {
            $numbers = $this->parseManualNumbers($recipientNumbers);
            $recipients = array_merge($recipients, $numbers);
        }

        # 3. Clean and validate
        return $this->cleanAndValidateNumbers($recipients);
    }

    private function parseManualNumbers($recipientNumbers)
    {
        return  $recipientNumbers;
    }

    private function cleanAndValidateNumbers($recipients)
    {
        $cleanRecipients = [];

        foreach ($recipients as $number) {
            # Remove non-digit characters
            $cleanNumber = preg_replace('/[^0-9]/', '', $number);

            # Skip invalid numbers
            if (strlen($cleanNumber) < 10) {
                continue;
            }

            # If number already starts with '234', keep it
            if (str_starts_with($cleanNumber, '234')) {
                $cleanRecipients[] = $cleanNumber;
            } #  If it starts with '0', remove the 0 and add '234'
            elseif (str_starts_with($cleanNumber, '0')) {
                $cleanRecipients[] = '234' . substr($cleanNumber, 1);
            }
        }

        return array_unique($cleanRecipients);
    }


    private function detectSmsType($message): string
    {
        # Check if message contains non-ASCII characters (Unicode)
        if (!mb_check_encoding($message, 'ASCII')) {
            return 'Unicode';
        }
        return 'Normal';
    }

    private function sendSmsToGateway($validated, $recipient): array
    {
        $params = $this->buildGatewayParams($validated, $recipient);
        $url = $this->smsConfig['url'] . '?' . http_build_query($params);
        try {

            // Use cURL exactly like your working PHP code
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if (curl_errno($ch)) {
                $error = curl_error($ch);
                curl_close($ch);
                Log::error("cURL error for {$recipient}: " . $error);
                return ['success' => false, 'error' => $error];
            }

            $response = curl_exec($ch);

            return $this->processCurlResponse($response, $httpCode, $recipient);

        } catch (\Exception $e) {
            Log::error("Gateway error for {$recipient}: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }


    private function buildGatewayParams($validated, $recipient): array
    {
        return [
            'username' => $this->smsConfig['username'],
            'password' => $this->smsConfig['password'],
            'from' => $this->getApprovedSenderId(),
            'to' => $recipient,
            'text' => $validated['message'], // Fixed: use actual message content
        ];
    }

    public function getApprovedSenderId()
    {
        $userId = Auth::id();
        $kyc = DB::table('kyc')->where('user_id', $userId)->first();

        if ($kyc && $kyc->status == 'approved') {
            return strtoupper($kyc->sender_id);

        } else {
            return $this->smsConfig['default_sender'];
        }
    }


    private function processCurlResponse($responseBody, $httpCode, $recipient): array
    {
        if ($httpCode !== 202) {
            Log::error("Gateway HTTP error for {$recipient}", [
                'status_code' => $httpCode,
                'body' => $responseBody
            ]);

            return [
                'success' => false,
                'error' => "Unable to process request,please try again "
            ];
        }

        $providerId = $this->extractProviderMessageId($responseBody);

        if ($this->isSuccessResponse($responseBody)) {
            return [
                'success' => true,
                'response' => $responseBody,
                'provider_id' => $providerId
            ];
        }

        Log::warning("SMS failed for {$recipient}: " . $responseBody);
        return [
            'success' => false,
            'error' => $responseBody
        ];
    }


    private function extractProviderMessageId($responseBody): ?string
    {
        $responseBody = trim($responseBody);

        // If it's a UUID, return it directly
        if (preg_match('/^[a-f0-9\-]{36}$/i', $responseBody)) {
            return $responseBody;
        }

        return null;
    }


    private function isSuccessResponse($body): bool
    {
        $body = trim(strtolower($body));

        if (preg_match('/^[a-f0-9\-]{36}$/i', $body)) {
            return true;
        }
    }


    private function calculateCredits($message): int
    {
        $messageLength = strlen($message);

        if ($messageLength <= 160) {
            return 1;
        } elseif ($messageLength <= 320) {
            return 2;
        } else {
            return ceil($messageLength / 160);
        }
    }


    public function saveTransactions($user, $type, $amount, $currentBalance): void
    {
        $amountAfter = $type === 'debit'
            ? $currentBalance - $amount
            : $currentBalance + $amount;

        DB::table('transactions')->insert([
            'user_id'           => $user->id,
            'amount'            => $amount,
            'type'              => $type,
            'payment_reference' => Utility::txRef("SMS", "system"),
            'amount_before'     => $currentBalance,
            'amount_after'      => $amountAfter,
            'status'            => 'completed',
            'created_at'        => now(), // manually add timestamps when using DB::table
            'updated_at'        => now(),
        ]);

        DB::table('wallets')
            ->where('user_id', $user->id)
            ->update(['amount' => $amountAfter]);
    }
}
