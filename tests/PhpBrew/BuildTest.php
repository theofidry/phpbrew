<?php

namespace PhpBrew\Tests;

use PhpBrew\Build;
use PHPUnit\Framework\TestCase;

/**
 * @small
 * @internal
 */
class BuildTest extends TestCase
{
    public function test_build_api(): void
    {
        $build = new Build('5.3.1');

        self::assertSame(1, $build->compareVersion('5.3.0'));
        self::assertSame(1, $build->compareVersion('5.3'));
        self::assertSame(-1, $build->compareVersion('5.4.0'));
        self::assertSame(-1, $build->compareVersion('5.4'));
    }

    public function test_neutral_virtual_variant(): void
    {
        $build = new Build('5.5.0');
        $build->enableVariant('neutral');
        $build->resolveVariants();

        self::assertTrue($build->isEnabledVariant('neutral'));
    }
}
