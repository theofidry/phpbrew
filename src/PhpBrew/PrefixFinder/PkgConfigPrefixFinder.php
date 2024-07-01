<?php

namespace PhpBrew\PrefixFinder;

use PhpBrew\PrefixFinder;
use PhpBrew\Utils;

/**
 * The strategy of finding prefix using pkg-config.
 */
final class PkgConfigPrefixFinder implements PrefixFinder
{
    /**
     * @var string
     */
    private $package;

    /**
     * @param string $package
     */
    public function __construct($package)
    {
        $this->package = $package;
    }

    public function findPrefix()
    {
        return Utils::getPkgConfigPrefix($this->package);
    }
}
