<?php

class ErrorHandler
{

    public static function handleError(
        int $errno,
        string $errstr,
        string $errfile,
        int $errline
    ): void {
        throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
    }
    /**
     * Handle Error Exception into json format
     *
     * @param Throwable $exception
     * @return void
     */

    public static function handleException(Throwable $exception): void
    {
        $statusCode = 500;
        $errorCode = $exception->getCode();
        $errorMessage = "Internal Server Error";

        if ($exception instanceof \InvalidArgumentException) {
            $statusCode = 400;
            $errorMessage = "Bad Request";
        } elseif ($exception instanceof \RuntimeException) {
            $statusCode = 403;
            $errorMessage = "Forbidden";
        }

        http_response_code($statusCode);

        echo json_encode([
            "status" => "error",
            "code" => $errorCode,
            "message" => $exception->getMessage(),
            "file" => basename($exception->getFile()),
            "line" => $exception->getLine(),
            //  "trace" => $exception->getTrace()
        ]);
    }
}
