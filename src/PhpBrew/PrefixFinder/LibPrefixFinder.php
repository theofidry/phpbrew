<?php

declare(strict_types=1);

namespace PhpBrew\PrefixFinder;

use PhpBrew\PrefixFinder;
use PhpBrew\Utils;

/**
 * The strategy of finding prefix using library file path.
 */
final class LibPrefixFinder implements PrefixFinder
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
        return Utils::findLibPrefix($this->path);
    }
}
