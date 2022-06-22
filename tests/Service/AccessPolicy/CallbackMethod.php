<?php

namespace AAM\UnitTest\Service\AccessPolicy;

class CallbackMethod
{

    public static function getSimpleValue()
    {
        return 'hello';
    }

    public static function getConditionalValue($i)
    {
        return $i === 'a' ? 1 : 2;
    }

    public static function getComplexValueFromObject()
    {
        return (object) [
            'a' => 'test'
        ];
    }

    public static function getComplexValueFromArray()
    {
        return [
            'a' => 'another-test'
        ];
    }

}