<?php

abstract class Model
{

    public $conn;

    public function __construct(Database $database = null)
    {
        $this->conn = $database ? $database->connect() : null;
    }

    #connectToBulkSms:: This method links the platfrom to BULKSMS
    /**
     * @param $form_data_array
     * @return mixed
     */
    public function connectToBulkSms($form_data_array)
    {
        $response = ['status' => false, 'message' => '', 'data' => []];

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => "https://www.bulksmsnigeria.com/api/v2/sms",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode($form_data_array), #  Send data as JSON
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Content-Type: application/json',
            ],
        ]);

        $result = curl_exec($curl);

        if ($result === false) {
            $response['message'] = "Unable to process request. Please try again.";
        } else {
            $decodedResult = json_decode($result, true);
            #  echo json_encode($decodedResult);

            if (isset($decodedResult['data']['status']) && $decodedResult['data']['status'] === "success") {
                $response['status'] = true;
                $response['message'] = $decodedResult['data']['message'];
                $response['data'] = $decodedResult['data'];
            } else {
                $response['message'] = $decodedResult['error']['message'] ?? 'Unexpected response, please try again later';
            }
        }

        curl_close($curl);
        return $response;
    }

    public function outputData($success = null, $message = null, $data = [], $status_code = 200)
    {
        http_response_code($status_code);

        $arr_output = array(
            'success' => $success,
            'message' => $message,
            'data' => $data,
            'status_code' => $status_code,
        );
        echo json_encode($arr_output);
    }
}
