<?php

namespace AAM\UnitTest\Service\DeniedRedirect;

class Callback
{
    const OUTPUT = 'Redirect Callback Output';

    public static function printOutput()
    {
        echo self::OUTPUT;
    }

}