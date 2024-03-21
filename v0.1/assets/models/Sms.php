<?php

class Sms extends Model
{

    public function sendSmsViaSendTruly($data, $usertoken)
    {

        $response = ['success' => false, 'message' => '', 'data' => '', 'status_code' => 200];

        $countPhoneNumber = count($data['to']) * $_ENV['SMS_CHARGES'];

        $generateMessageId = $this->generateMessageId();

        $checkAccountBalance = $this->checkAccountBalance($usertoken);
        if (!$checkAccountBalance['status']) {
            $response['message'] = $checkAccountBalance['message'];
            return $response;
        }

        $sms_cost = $this->calculatePrice($data['message'], $countPhoneNumber);

        if ($checkAccountBalance['account_balance'] >= $sms_cost['price']) {
            $saveTranxRecord = $this->saveTranxRecord($usertoken, "debit", $sms_cost['price'], $checkAccountBalance['account_balance']);
            if (!$saveTranxRecord['status']) {
                $response['message'] = $saveTranxRecord['message'];
                return $response;
            }
            $smsDataArray = [
                'api_token' => $_ENV['SMS_API_TOKEN'],
                'from' => $data['sender_id'],
                'to' => $data['to'],
                'body' => $data['message'],
                'callback_url' => $_ENV['BULK_SMS_CALLBACK_URL'],
                'gateway' => $data['delivery_route'],
            ];

            $connectToBulkSms = $this->connectToBulkSms($smsDataArray);
            if (!$connectToBulkSms['status']) {
                if (strpos($connectToBulkSms['message'], 'non-Nigerian') !== false) {
                    $response['message'] = "Failed to send SMS. Please try again";
                } else {
                    $response['message'] = $connectToBulkSms['message'];
                }

                return $response;
            }
            $roundedCost = round($connectToBulkSms['data']['cost'], 2, PHP_ROUND_HALF_UP);
            $schedule = false;

            $saveUserMsgTranx = $this->saveSmsLogs(
                $generateMessageId,
                $connectToBulkSms['data']['message_id'],
                $roundedCost,
                $sms_cost['price'],
                $connectToBulkSms['data']['gateway_used'],
                $usertoken,
                $data['message'],
                $data['sender_id'],
                'standard'
            );
            if (!$saveUserMsgTranx['status']) {
                $response['message'] = $saveUserMsgTranx['message'];
                return $response;
                #  $response['status_code'] =
            }

            $response['success'] = true;
            $response['message'] = $connectToBulkSms['message'];
            $response['data'] = ['sms_analysis' => $sms_cost];
        } else {
            $response['message'] = "Insufficient funds. Kindly fund wallet to coutinue";
        }
        return $response;
    }

    #saveSmsLogs This method saves SMS logs after Sms is sent
    /**
     * @param $message_token
     * @param $sms_message_id
     * @param $cost
     * @param $gateway_used
     * @param $usertoken
     * @param $message
     * @param $schedule
     * @param $list_token
     * @param $senderid
     * @param $send_truly_cost
     * @return mixed
     */
    public function saveSmsLogs($message_token, $sms_message_id, $cost, $send_truly_cost, $gateway_used, $usertoken, $message, $senderid, $message_type)
    {
        $response = ["status" => false, "message" => "", "data" => "", "status_code" => 200];
        $time = time();

        $schedule = false;

        try {
            $insertSql = 'INSERT INTO tblmessage_records (message_token, sms_message_id, cost, send_truly_cost,  gateway_used, usertoken, body, schedule,senderid, message_type, time)
            VALUES (:message_token, :sms_message_id, :cost, :send_truly_cost, :gateway_used, :usertoken, :message, :schedule, :senderid, :message_type, :time)'; #  Insert with
            $updateStmt = $this->conn->prepare($insertSql);
            $updateStmt->bindParam(':message_token', $message_token, PDO::PARAM_STR);
            $updateStmt->bindParam(':sms_message_id', $sms_message_id, PDO::PARAM_STR);
            $updateStmt->bindParam(':cost', $cost, PDO::PARAM_STR);
            $updateStmt->bindParam(':send_truly_cost', $send_truly_cost, PDO::PARAM_STR);
            $updateStmt->bindParam(':gateway_used', $gateway_used, PDO::PARAM_STR);
            $updateStmt->bindParam(':usertoken', $usertoken, PDO::PARAM_STR);
            $updateStmt->bindParam(':message', $message, PDO::PARAM_STR);
            $updateStmt->bindParam(':schedule', $schedule, PDO::PARAM_STR);
            $updateStmt->bindParam(':senderid', $senderid, PDO::PARAM_STR);
            $updateStmt->bindParam(':message_type', $message_type, PDO::PARAM_STR);
            $updateStmt->bindParam(':time', $time, PDO::PARAM_STR);

            $updateStmt->execute();

            // $rescheduleListSMS = $this->rescheduleListSMS($list_token, $usertoken, $message_token);
            // if (!$rescheduleListSMS['status']) {
            //     $response['message'] = $rescheduleListSMS['message'];
            // }

            $response['status'] = true;
            $response['message'] = " Logs saved sucessfully";
        } catch (PDOException $e) {
            $response['message'] = "Error" . $e->getMessage();
            $response['status_code'] = 500;
        } finally {
            $updateStmt = null;
        }
        return $response;
    }

    public function generateMessageId()
    {
        $randomString = uniqid(); // Generates a unique ID based on the current timestamp
        $randomString .= '-' . bin2hex(random_bytes(1)); // Appends a random hex character
        $randomString .= '-' . bin2hex(random_bytes(2)); // Appends two more random hex characters
        $randomString .= '-' . bin2hex(random_bytes(2)); // Appends two more random hex characters
        $randomString .= '-' . bin2hex(random_bytes(6)); // Appends six more random hex characters
        return $randomString;
    }

    public function calculatePricePages($text, $pricePerPage)
    {
        $charactersPerPage = 160;
        $totalCharacters = strlen($text);
        $totalPages = ceil($totalCharacters / $charactersPerPage);

        $lastPageCharacters = $totalCharacters % $charactersPerPage;
        $lastPagePrice = $pricePerPage * $totalPages;

        $pageDetails = [
            'total_pages' => $totalPages,
            'last_page_characters' => $lastPageCharacters,
            'total_characters' => $totalCharacters,
            'price' => $lastPagePrice,
        ];

        return $pageDetails;
    }

    public function calculatePrice($smsText, $pricePerPage)
    {
        $calculatePricePages = $this->calculatePricePages($smsText, $pricePerPage);
        return $calculatePricePages;
    }

    #checkAccountBalance:: This method checks for an account Balance
    public function checkAccountBalance($usertoken)
    {
        $response = ["status" => false, "account_balance" => '', "message" => "", "status_code" => 200];

        try {
            $balanceSelectSql = 'SELECT amount   FROM tblwallet WHERE usertoken = :usertoken';
            $selectStmt = $this->conn->prepare($balanceSelectSql); // Corrected variable name

            # Bind values
            $selectStmt->bindParam(':usertoken', $usertoken, PDO::PARAM_STR);

            # Execute the SELECT query
            $selectStmt->execute();

            # Fetch the account balance
            $account_balance = $selectStmt->fetch(PDO::FETCH_ASSOC);
            $response['status'] = true;
            $response['account_balance'] = $account_balance['amount'];
            $response['message'] = "Account Balance Retrieved successfully";
        } catch (PDOException $e) {
            $response['message'] = "Error retrieving account balance: " . $e->getMessage();
            $response['status_code'] = 500;
        } finally {
            $selectStmt = null;
            // $this->conn = null;
        }

        return $response;
    }

    #saveTranxRecord::This method saves all traansaction History belonging to users
    public function saveTranxRecord($usertoken, $credit_type, $amount, $prev_balance, $paystack_trax_id = 0)
    {
        $response = ["status" => false, "message" => "", "data" => [], "status_code" => 200];
        $time = time();
        try {
            $insertSql = 'INSERT INTO tbltranx (usertoken, credit_type, amount, prev_balance, paystack_trax_id, time)
            VALUES (:usertoken, :credit_type, :amount, :prev_balance, :paystack_trax_id, :time)';
            $insertStmt = $this->conn->prepare($insertSql);

            # Bind constant values outside the loop
            $insertStmt->bindParam(':usertoken', $usertoken, PDO::PARAM_STR);
            $insertStmt->bindParam(':credit_type', $credit_type, PDO::PARAM_STR);

            $insertStmt->bindParam(':amount', $amount, PDO::PARAM_STR);
            $insertStmt->bindParam(':prev_balance', $prev_balance, PDO::PARAM_STR);
            $insertStmt->bindParam(':paystack_trax_id', $paystack_trax_id, PDO::PARAM_STR);
            $insertStmt->bindParam(':time', $time, PDO::PARAM_INT);
            $insertStmt->execute();

            $userPlan = $this->getUserPlan($usertoken);

            $updateUserAccountBalance = $this->updateUserAccountBalance($amount, $usertoken, $credit_type);
            if (!$updateUserAccountBalance['status']) {
                $response['message'] = $updateUserAccountBalance['message'];
                $response['status_code'] = $updateUserAccountBalance['status_code'];
                return $response;
            }

            $response['status'] = true;
            $response['message'] = "Record Saved";
        } catch (PDOException $e) {
            $response['message'] = "Error while saving transaction: " . $e->getMessage();
            $response['status_code'] = 500;
        } finally {
            $insertStmt = null;
        }
        return $response;
    }

    #getUserPlan::This method gets a user's current plan
    public function getUserPlan($usertoken)
    {
        $response = ['status' => false, 'sms_count' => '', 'plan_type' => null, 'message' => null, 'status_code' => 200];

        try {
            $sql = "SELECT * FROM tblsubscription_plan WHERE usertoken = :usertoken";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':usertoken', $usertoken);
            $stmt->execute();

            $userPlan = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($userPlan === false) {
                $response['message'] = "User does not have an active plan";
                $response['status_code'] = 200;
            } else {
                $response['plan_type'] = $userPlan['plan_type'];
                $response['sms_count'] = $userPlan['sms_count'];
                $response['status'] = true;
                $response['message'] = "Plan retrieved successfully";
            }
        } catch (PDOException $e) {
            $response['message'] = "Error: " . $e->getMessage();
            $response['status_code'] = 500;
        }

        return $response;
    }

    #updateUserAccountBalance ::This method updateUserAccountBalance debits or credit user account depending on the cryeditType.
    public function updateUserAccountBalance($amount, $usertoken, $credit_type)
    {
        $operator = $credit_type === 'credit' ? '+' : '-';

        $response = ["status" => false, "message" => "", "data" => [], "status_code" => 200];

        $sql = "UPDATE tblwallet
                SET amount = amount $operator :amount
                WHERE usertoken = :usertoken";

        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':amount', $amount);
            $stmt->bindParam(':usertoken', $usertoken);

            $stmt->execute();
            $response['status'] = true;
            $response['message'] = "Balance updated successfully";
        } catch (PDOException $e) {
            $response['message'] = "Unable to update balance" . $e->getMessage();
            $response['status_code'] = 500;
        } finally {
            $stmt = null;
        }
        return $response;
    }
}
