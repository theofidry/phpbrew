<?php

declare(strict_types=1);

namespace PhpBrew\Tests;

use PhpBrew\Utils;
use PHPUnit\Framework\TestCase;

/**
 * @small
 * @internal
 */
class UtilsTest extends TestCase
{
    public function test(): void
    {
        self::assertInternalType('boolean', Utils::support64bit());
    }

    public function test_lookup_prefix(): void
    {
        self::assertNotEmpty(Utils::getLookupPrefixes());
    }

    public function test_find_icu_pkg_data(): void
    {
        self::markTestSkipped('icu/pkgdata.inc is not found on Ubuntu Linux');
        self::assertNotNull(Utils::findLibPrefix('icu/pkgdata.inc', 'icu/Makefile.inc'));
    }

    public function test_prefix(): void
    {
        self::assertNotNull(Utils::findIncludePrefix('openssl/opensslv.h'));
    }

    public function test_findbin(): void
    {
        self::assertNotNull(Utils::findBin('ls'));
    }
}
