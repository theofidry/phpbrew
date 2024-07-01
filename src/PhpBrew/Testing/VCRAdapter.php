<?php

declare(strict_types=1);

namespace PhpBrew\Testing;

use VCR\VCR;

class VCRAdapter
{
    public static function enableVCR($testInstance): void
    {
        VCR::turnOn();
        VCR::insertCassette(self::getVCRCassetteName($testInstance));
    }

    public static function disableVCR(): void
    {
        VCR::eject();
        VCR::turnOff();
    }

    protected static function getVCRCassetteName($testInstance)
    {
        $classname_parts = explode('\\', get_class($testInstance));

        return implode('/', array_slice($classname_parts, -2, 2));
    }
}
