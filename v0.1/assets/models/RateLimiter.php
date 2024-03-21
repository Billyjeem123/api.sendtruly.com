<?php

class RateLimiter
{
    private $storage = [];

    public function limitRequests($usertoken, $ipAddress, $limit = 2, $interval = 60)
    {
        $key = "ratelimit:$usertoken:$ipAddress";
        $currentTime = time();

        $response = ["status" => false, "usertoken" => $usertoken, 'ip_address' => $ipAddress, 'remaining_attempts' => 0, 'data' => [], "message" => "", "status_code" => 429];

        if (!isset($this->storage[$key])) {
            $this->storage[$key] = ['count' => 1, 'timestamp' => $currentTime];
            $response['status'] = true;
            $response['message'] = "Request allowed";
            $response['remaining_attempts'] = $limit - 1;
            $response['status_code'] = 200;
            // return $response;
        }

        $data = $this->storage[$key];

        if ($currentTime - $data['timestamp'] > $interval) {
            $data = ['count' => 1, 'timestamp' => $currentTime];
        } else {
            $data['count']++;
        }

        $this->storage[$key] = $data;

        $remainingAttempts = max(0, $limit - $data['count']);
        $status = $data['count'] <= $limit;

        $response['status'] = $status;
        $response['message'] = $status ? 'Request allowed' : 'Rate limit exceeded';
        $response['remaining_attempts'] = $remainingAttempts;
        $response['status_code'] = $status ? 200 : 429;

        $response['debug_info'] = [
            'current_time' => $currentTime,
            'storage' => $this->storage[$key] ?? 'Key not found',
        ];

        return $response;
    }
}
