<?php
ob_start();
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, Content-Length, X-Requested-With');
header('Content-Type: application/json;charset=utf-8');

require_once $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';

set_error_handler('ErrorHandler::handleError');
set_exception_handler('ErrorHandler::handleException');

use Dotenv\Dotenv;

// Check the host in the HTTP headers
$host = $_SERVER['HTTP_HOST'];

// Define the path for the environment file based on the host
if ($host === 'dev.api.sendtruly.com') {
    $envPath = $_SERVER['DOCUMENT_ROOT'] . '/.env.dev';
    header('Access-Control-Allow-Origin: *'); // Allow all origins in test environment
} elseif ($host === 'api.sendtruly.com') {
    $envPath = $_SERVER['DOCUMENT_ROOT'] . '/.env';
} else {
    // Default to .env.dev for other hosts
    $envPath = $_SERVER['DOCUMENT_ROOT'] . '/.env.dev';
    header('Access-Control-Allow-Origin: *'); // Allow all origins in dev environment
}

$envPath = str_replace('\\', '/', $envPath);

$dotenv = Dotenv::createImmutable(dirname($envPath), basename($envPath));
$dotenv->load();

$headers = apache_request_headers();
$authHeader = $headers['Authorization'] ?? "null";
$token = (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) ? $matches[1] : "null";

$db = new Database();
$auth = new Auth($db);

$authenticationResult = $auth->autheticateAPIKEY($token);
if (!$authenticationResult['status']) {
    $auth->outputData(false, $authenticationResult['message'], [], $authenticationResult['status_code']);
    exit;
}

$rateLimiter = new RateLimiter();
$userIpAdress = Utility::getUserIP();
$result = $rateLimiter->limitRequests($authenticationResult['usertoken'], $userIpAdress);
