<?php

namespace AAM\UnitTest\Service\Uri;

class Callback
{
    const REDIRECT_URL = 'https://aamplugin.com/redirect';

    public static function redirectCallback()
    {
        array_push($GLOBALS['UT_HTTP_HEADERS'], 'Location: ' . self::REDIRECT_URL);
    }

}