<?php

namespace AAM\UnitTest\Service\LogoutRedirect;

class Callback
{
    const REDIRECT_URL = 'https://aamportal.com/redirect';

    public static function redirectCallback()
    {
        array_push($GLOBALS['UT_HTTP_HEADERS'], 'Location: ' . self::REDIRECT_URL);
    }

}