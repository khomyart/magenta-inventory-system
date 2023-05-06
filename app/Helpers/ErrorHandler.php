<?php

namespace App\Helpers;

class ErrorHandler {
    public static $availableErrorCodes = [
        422, //uprocessed content
        401, //unauthorized = not enough rights
        403, //unauthenticated = trying to get access as guest
        404, //not found
    ];

    /**
     * Responses with given message and status code
     *
     * @param string  $message
     * @param integer $code
     */
    public static function responseWith($message, $code = 422) {
        if (!in_array($code, self::$availableErrorCodes)) {
            throw new \Exception("Unacceptable response code", 1);
        }

        return response(["message" => $message], $code);
    }
}
