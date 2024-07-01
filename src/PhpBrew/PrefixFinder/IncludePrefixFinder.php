<?php

declare(strict_types=1);

namespace PhpBrew\PrefixFinder;

use PhpBrew\PrefixFinder;
use PhpBrew\Utils;

/**
 * The strategy of finding prefix using include paths.
 */
final class IncludePrefixFinder implements PrefixFinder
{
    /**
     * @var string
     */
    private $path;

    /**
     * @param string $path
     */
    public function __construct($path)
    {
        $this->path = $path;
    }

    public function findPrefix()
    {
        return Utils::findIncludePrefix($this->path);
    }
}
