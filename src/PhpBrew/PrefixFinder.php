<?php

declare(strict_types=1);

namespace PhpBrew;

/**
 * A strategy of finding prefix.
 */
interface PrefixFinder
{
    /**
     * Returns the found prefix or NULL of it's not found.
     *
     * @return string|null
     */
    public function findPrefix();
}
