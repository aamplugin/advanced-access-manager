<?php

namespace AAM\UnitTest\Service\Uri;

class Callback
{
    const REDIRECT_URL = 'https://aamplugin.com/redirect';

    public static function redirectCallback()
    {
        header('Location: ' . self::REDIRECT_URL);
    }

}