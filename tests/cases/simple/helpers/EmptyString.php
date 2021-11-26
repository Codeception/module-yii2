<?php


namespace app\simple\helpers;

/**
 * Class that is empty when converted to string
 */
class EmptyString
{
    private $value;

    public function __construct($value)
    {
        $this->value = $value;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function __toString()
    {
        return '';
    }
}