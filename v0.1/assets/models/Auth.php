<?php

class Auth extends Model
{

    #checkAccountBalance:: This method authenticates  users integrating snedtruly platform
    public function autheticateAPIKEY(string $api_key): mixed
    {
        $response = ["status" => false, "usertoken" => '', "message" => "", "status_code" => 200];

        try {
            $sql = 'SELECT *   FROM tblapi_key WHERE api_key = :api_key';
            $selectStmt = $this->conn->prepare($sql); // Corrected variable name

            # Bind values
            $selectStmt->bindParam(':api_key', $api_key, PDO::PARAM_STR);

            # Execute the SELECT query
            $selectStmt->execute();

            # Fetch the account balance
            $userData = $selectStmt->fetch(PDO::FETCH_ASSOC);
            if (empty($userData) || $userData === false) {
                $response['message'] = "Invalid API Key";
                $response['status_code'] = 401;
                return $response;
            }

            $response['status'] = true;
            $response['usertoken'] = $userData['usertoken'];
        } catch (PDOException $e) {
            $response['message'] = "Error retrieving user info: " . $e->getMessage();
            $response['status_code'] = 500;
        } finally {
            $selectStmt = null;
        }

        return $response;
    }
}
