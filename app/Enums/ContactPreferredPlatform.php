<?php

namespace App\Enums;

class ContactPreferredPlatform
{
    const TELEGRAM = 'telegram';

    const WHATSAPP = 'whatsapp';

    const SMS = 'sms';

    const EMAIL = 'email';

    const VIBER = 'viber';

    const CALL = 'call';

    const OTHER = 'other';

    public static function getValues(): array
    {
        $reflectionClass = new \ReflectionClass(self::class);

        return array_values($reflectionClass->getConstants());
    }
}
