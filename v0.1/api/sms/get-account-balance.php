<?php
require_once '../../assets/initializer.php';

$sms = new Sms($db);
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('HTTP/1.1 405 Method Not Allowed');
}

$checkAccountBalance = $sms->checkAccountBalance($authenticationResult['usertoken']);
if (!$checkAccountBalance['status']) {
    return $sms->outputData(false, $checkAccountBalance['message'], [], $checkAccountBalance['status_code']);
}

return $sms->outputData(true, $checkAccountBalance['message'], ['account_balance' => $checkAccountBalance['account_balance']], $checkAccountBalance['status_code']);
