<?php

namespace PhpBrew\Patches;

use CLIFramework\Logger;
use PhpBrew\Build;
use PhpBrew\Testing\PatchTestCase;

/**
 * @internal
 */
class FreeTypePatchTest extends PatchTestCase
{
    public function test_patch(): void
    {
        $logger = new Logger();
        $logger->setQuiet();

        $sourceDirectory = getenv('PHPBREW_BUILD_PHP_DIR');

        $this->setupBuildDirectory('7.3.12');

        $build = new Build('7.3.12');
        $build->setSourceDirectory($sourceDirectory);
        $build->enableVariant('gd');

        $patch = new FreeTypePatch();
        self::assertTrue($patch->match($build, $logger));
        self::assertGreaterThan(0, $patch->apply($build, $logger));

        $expectedDirectory = getenv('PHPBREW_EXPECTED_PHP_DIR') . DIRECTORY_SEPARATOR . '7.3.12-freetype-patch';
        self::assertFileEquals($expectedDirectory . '/ext/gd/config.m4', $sourceDirectory . '/ext/gd/config.m4');
    }
}
