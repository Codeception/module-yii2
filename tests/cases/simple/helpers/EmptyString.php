<?php

declare(strict_types=1);

namespace app\simple\helpers;

/**
 * Class that is empty when converted to string
 */
final class EmptyString
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
