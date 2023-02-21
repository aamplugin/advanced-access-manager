<?php

namespace AAM\UnitTest\Service\Content;

class Callback
{
    const REDIRECT_URL = 'https://aamportal.com/redirect';

    public static function redirectCallback()
    {
        return self::REDIRECT_URL;
    }

}