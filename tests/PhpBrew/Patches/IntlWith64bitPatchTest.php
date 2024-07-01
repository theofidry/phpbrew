<?php

declare(strict_types=1);

namespace PhpBrew\Tests\Patches;

use CLIFramework\Logger;
use PhpBrew\Build;
use PhpBrew\Patches\IntlWith64bitPatch;
use PhpBrew\Testing\PatchTestCase;

/**
 * @internal
 */
class IntlWith64bitPatchTest extends PatchTestCase
{
    public function test_patch(): void
    {
        $logger = new Logger();
        $logger->setQuiet();

        $fromVersion = '5.3.29';
        $sourceFixtureDirectory = getenv('PHPBREW_FIXTURES_PHP_DIR') . DIRECTORY_SEPARATOR . $fromVersion;
        $sourceDirectory = getenv('PHPBREW_BUILD_PHP_DIR');

        if (!is_dir($sourceDirectory)) {
            self::markTestSkipped("{$sourceDirectory} does not exist.");
        }

        // Copy the source Makefile to the Makefile
        // copy($sourceFixtureDirectory . '/Makefile', $sourceDirectory . '/Makefile');
        $this->setupBuildDirectory($fromVersion);

        $build = new Build($fromVersion);
        $build->setSourceDirectory($sourceDirectory);
        $build->enableVariant('intl');
        self::assertTrue($build->isEnabledVariant('intl'));

        $patch = new IntlWith64bitPatch();
        $matched = $patch->match($build, $logger);
        self::assertTrue($matched, 'patch matched');
        $patchedCount = $patch->apply($build, $logger);
        self::assertEquals(3, $patchedCount);

        $sourceExpectedDirectory = getenv('PHPBREW_EXPECTED_PHP_DIR') . DIRECTORY_SEPARATOR . $fromVersion;
        self::assertFileEquals($sourceExpectedDirectory . '/Makefile', $sourceDirectory . '/Makefile');
    }
}
