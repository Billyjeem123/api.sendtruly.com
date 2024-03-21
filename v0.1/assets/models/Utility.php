 <?php

 class Utility
 {
     #  Function to generate OTP
     public function generateNumericOTP($n)
     {

         #  Take a generator string which consist of
         #  all numeric digits
         $generator = "1357902468";

         #  Iterate for n-times and pick a single character
         #  from generator and append it to $result

         #  Login for generating a random character from generator
         #      ---generate a random number
         #      ---take modulus of same with length of generator (say i)
         #      ---append the character at place (i) from generator to result

         $result = "";

         for ($i = 1; $i <= $n; $i++) {
             $result .= substr($generator, (rand() % (strlen($generator))), 1);
         }

         #  Return result
         return $result;
     }

     public static function generateAlphaNumericOTP($n)
     {

         #  Take a generator string which consist of
         #  all numeric digits
         $generator = "1357902468ABCDEFGHIJKLMNOPQRSTUVWXYZ";

         #  Iterate for n-times and pick a single character
         #  from generator and append it to $result

         #  Login for generating a random character from generator
         #      ---generate a random number
         #      ---take modulus of same with length of generator (say i)
         #      ---append the character at place (i) from generator to result

         $result = "";

         for ($i = 1; $i <= $n; $i++) {
             $result .= substr($generator, (rand() % (strlen($generator))), 1);
         }

         #  Return result
         return $result;
     }

     public static function generatealphanumericotpCase($n)
     {

         #  Take a generator string which consist of
         #  all numeric digits
         $generator = "1357902468ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz";

         #  Iterate for n-times and pick a single character
         #  from generator and append it to $result

         #  Login for generating a random character from generator
         #      ---generate a random number
         #      ---take modulus of same with length of generator (say i)
         #      ---append the character at place (i) from generator to result

         $result = "";

         for ($i = 1; $i <= $n; $i++) {
             $result .= substr($generator, (rand() % (strlen($generator))), 1);
         }

         #  Return result
         return $result;
     }

     public static function generatealphanumericotpSymbol($n)
     {

         #  Take a generator string which consist of
         #  all numeric digits
         $generator = "1357902468ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz-_@!";

         #  Iterate for n-times and pick a single character
         #  from generator and append it to $result

         #  Login for generating a random character from generator
         #      ---generate a random number
         #      ---take modulus of same with length of generator (say i)
         #      ---append the character at place (i) from generator to result

         $result = "";

         for ($i = 1; $i <= $n; $i++) {
             $result .= substr($generator, (rand() % (strlen($generator))), 1);
         }

         #  Return result
         return $result;
     }

     public static function generateAlphaOTP($n)
     {

         #  Take a generator string which consist of
         #  all numeric digits
         $generator = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";

         #  Iterate for n-times and pick a single character
         #  from generator and append it to $result

         #  Login for generating a random character from generator
         #      ---generate a random number
         #      ---take modulus of same with length of generator (say i)
         #      ---append the character at place (i) from generator to result

         $result = "";

         for ($i = 1; $i <= $n; $i++) {
             $result .= substr($generator, (rand() % (strlen($generator))), 1);
         }

         #  Return result
         return $result;
     }

     #  validate email
     public static function validateEmail(string $mail)
     {
         #  code...

         if (!filter_var($mail, FILTER_VALIDATE_EMAIL)) {
             return false;
         }
         return true;
     }

     #formatDate::This method format date to humna readable format

     public static function formatDate($time)
     {

         return date('D d M, Y', $time);
     }

     #v::This method format date to amount readable fomat

     public static function formatCurrency($amount)
     {

         return number_format($amount, 2);
     }

     public static function diffForHumans($timestamp)
     {

         $current_time = time();

         $difference_in_seconds = $current_time - $timestamp;

         if ($difference_in_seconds < 60) {
             return "Just now";
         } elseif ($difference_in_seconds < 3600) {
             return floor($difference_in_seconds / 60) . " minutes ago";
         } elseif ($difference_in_seconds < 86400) {
             return floor($difference_in_seconds / 3600) . " hours ago";
         } elseif ($difference_in_seconds < 604800) {
             return floor($difference_in_seconds / 86400) . " days ago";
         } elseif ($difference_in_seconds < 2592000) {
             $weeks = floor($difference_in_seconds / 604800);
             return $weeks . " " . ($weeks === 1 ? "week" : "weeks") . " ago";
         } elseif ($difference_in_seconds < 31104000) {
             return floor($difference_in_seconds / 2592000) . " months ago";
         } else {
             return floor($difference_in_seconds / 31104000) . " years ago";
         }
     }

     public static function getMemoryUsage()
     {
         $mem_usage = memory_get_usage(true);
         if ($mem_usage < 1024) {
             return $mem_usage . ' bytes';
         } elseif ($mem_usage < 1048576) {
             return round($mem_usage / 1024, 2) . ' KB';
         } else {
             return round($mem_usage / 1048576, 2) . ' MB';
         }
     }

     public static function checkSize()
     {
         $memory_usage = self::getMemoryUsage();
         echo 'Memory usage: ' . $memory_usage;
     }

     public static function token()
     {
         return mt_rand(100000, 999999);
     }

     /**
      * sanitizeInput Parameters
      *
      * @param [ type ] $input
      * @return string
      */
     private static function sanitizeInput($input)
     {
         // Check if the input is an array
         if (is_array($input)) {
             // Validate and sanitize each element in the array
             foreach ($input as &$element) {
                 // Check if the element is not null before applying trim
                 if ($element !== null) {
                     // Remove white space from beginning and end of each element
                     $element = trim($element);
                     // Remove slashes
                     $element = stripslashes($element);
                     // Convert special characters to HTML entities
                     $element = htmlspecialchars($element, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                 }
             }
             unset($element); // unset to avoid potential side effects

             return $input;
         }

         // For non-array input, continue with the original sanitization process
         // Check if the input is not null before applying trim
         if ($input !== null) {
             // Remove white space from beginning and end of string
             $input = trim($input);
             // Remove slashes
             $input = stripslashes($input);
             // Convert special characters to HTML entities
             $input = htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
         }

         return $input ?? "";
     }
     public static function validateRequiredParams($data, $validKeys)
     {
         $errors = [];

         # Check for invalid keys
         $invalidKeys = array_diff(array_keys($data), $validKeys);
         if (!empty($invalidKeys)) {
             foreach ($invalidKeys as $key) {
                 if ($key !== 'is_drafted' && empty($data[$key])) {
                     $errors[] = "$key is not a valid input field";
                 }
             }
         }

         if (!empty($errors)) {
             self::validateRequestParameters($errors, 400); // 400 Bad Request
             return;
         }

         # Check for empty fields
         foreach ($validKeys as $key) {
             // Exclude specific keys from the empty field check
             if (!in_array($key, ['is_drafted', 'scheduled']) && empty($data[$key])) {
                 $errors[] = ($key) . ' is required';
             }
         }

         if (!empty($errors)) {
             self::responseToEmptyFields($errors, 400); // 400 Bad Request
             return;
         }

         # Sanitize input
         foreach ($validKeys as $key) {
             $data[$key] = self::sanitizeInput($data[$key]);
         }

         return $data;
     }

     #  resourceNotFound::Check for id if exists

     private function resourceNotFound(int $id): void
     {

         echo json_encode(['message' => "Resource with id $id not found"]);
     }

     /**
      * validateRequestParameters alert of errors deteced
      *
      * @param array $errors
      * @return void
      */

     public static function validateRequestParameters(array $errors): void
     {

         self::outputData(false, 'Kindly review your request parameters to ensure they comply with our requirements.', $errors, 400);
     }

     public static function getUserIP()
     {
         $ipaddress = '';

         if (isset($_SERVER['HTTP_CLIENT_IP'])) {
             $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
         } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
             $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
         } elseif (isset($_SERVER['HTTP_X_FORWARDED'])) {
             $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
         } elseif (isset($_SERVER['HTTP_FORWARDED_FOR'])) {
             $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
         } elseif (isset($_SERVER['HTTP_FORWARDED'])) {
             $ipaddress = $_SERVER['HTTP_FORWARDED'];
         } elseif (isset($_SERVER['REMOTE_ADDR'])) {
             $ipaddress = $_SERVER['REMOTE_ADDR'];
         }

         return $ipaddress;
     }

     public static function responseToEmptyFields(array $errors): void
     {

         self::outputData(false, 'All fields are required', $errors, 400);
     }

     public static function outputData($success = null, $message = null, $data = null, $status_code = 200)
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
