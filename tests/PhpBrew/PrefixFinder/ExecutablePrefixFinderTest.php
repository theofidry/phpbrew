<?php

namespace PhpBrew\Tests;

use PhpBrew\PrefixFinder\ExecutablePrefixFinder;
use PHPUnit\Framework\TestCase;

/**
 * @group prefixfinder
 * @internal
 */
class ExecutablePrefixFinderTest extends TestCase
{
    public function test_find_valid(): void
    {
        $epf = new ExecutablePrefixFinder('ls');
        self::assertNotNull($epf->findPrefix());
    }

    public function test_find_invalid(): void
    {
        $epf = new ExecutablePrefixFinder('inexistent-binary');
        self::assertNull($epf->findPrefix());
    }
}
