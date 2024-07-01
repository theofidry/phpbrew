<?php

declare(strict_types=1);

namespace PhpBrew\PrefixFinder;

use PhpBrew\PrefixFinder;
use PhpBrew\Utils;

/**
 * The strategy of finding prefix by an executable file.
 */
final class ExecutablePrefixFinder implements PrefixFinder
{
    /**
     * @var string
     */
    private $name;

    /**
     * @param string $name Executable name
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    public function findPrefix()
    {
        $bin = Utils::findBin($this->name);

        if ($bin === null) {
            return null;
        }

        return dirname($bin);
    }
}
