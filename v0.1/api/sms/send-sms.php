<?php
require_once '../../assets/initializer.php';

$data = (array) json_decode(file_get_contents('php://input'), true);

$sms = new Sms($db);

$requiredKeys = ['sender_id', 'to', 'message', 'delivery_route'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    header('Allow: POST');
    exit();
}

if (!Utility::validateRequiredParams($data, $requiredKeys)) {
    return;
}
$sendUserSms = $sms->sendSmsViaSendTruly($data, $authenticationResult['usertoken']);
if (!$sendUserSms['success']) {
    return $sms->outputData(false, $sendUserSms['message'], $sendUserSms['data'], $sendUserSms['status_code']);
}

return $sms->outputData(true, $sendUserSms['message'], $sendUserSms['data'], $sendUserSms['status_code']);
