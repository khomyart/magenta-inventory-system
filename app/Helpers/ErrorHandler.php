<?php

namespace App\Helpers;

use Symfony\Component\HttpFoundation\Response;

class ErrorHandler
{
    public static $availableErrorCodes = [
        422, // unprocessed content
        412, // precondition failed = business rule violation
        401, // unauthorized = not enough rights
        403, // unauthenticated = trying to get access as guest
        404, // not found
    ];

    /**
     * Responses with given message and status code
     *
     * @param  string  $message
     * @param  int  $code
     * @return Response
     */
    public static function responseWith(string $message, int $code = 422): Response
    {
        if (! in_array($code, self::$availableErrorCodes)) {
            throw new \InvalidArgumentException('Unacceptable response code', 1);
        }

        return response(['message' => $message], $code);
    }
}
