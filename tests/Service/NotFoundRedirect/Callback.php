<?php

namespace AAM\UnitTest\Service\NotFoundRedirect;

class Callback
{
    const REDIRECT_URL = 'https://aamplugin.com/redirect';

    public static function redirectCallback()
    {
        header('Location: ' . self::REDIRECT_URL);
    }

}