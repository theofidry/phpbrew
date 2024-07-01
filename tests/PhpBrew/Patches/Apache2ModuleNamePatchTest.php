<?php

namespace PhpBrew\Tests\Patches;

use CLIFramework\Logger;
use PhpBrew\Build;
use PhpBrew\Patches\Apache2ModuleNamePatch;
use PhpBrew\Testing\PatchTestCase;

/**
 * @small
 * @internal
 */
class Apache2ModuleNamePatchTest extends PatchTestCase
{
    public static function versionProvider(): iterable
    {
        return [
            [
                '5.5.17',
                107,
                '/Makefile.global',
            ],
            [
                '7.4.0',
                25,
                '/build/Makefile.global',
            ],
        ];
    }

    /**
     * @dataProvider versionProvider
     * @param mixed $version
     * @param mixed $expectedPatchedCount
     * @param mixed $makefile
     */
    public function test_patch_version($version, $expectedPatchedCount, $makefile): void
    {
        $logger = new Logger();
        $logger->setQuiet();

        $sourceDirectory = getenv('PHPBREW_BUILD_PHP_DIR');

        if (!is_dir($sourceDirectory)) {
            self::markTestSkipped("{$sourceDirectory} does not exist.");
        }

        $this->setupBuildDirectory($version);

        $build = new Build($version);
        $build->setSourceDirectory($sourceDirectory);
        $build->enableVariant('apxs2');
        self::assertTrue($build->isEnabledVariant('apxs2'));

        $patch = new Apache2ModuleNamePatch($version);
        $matched = $patch->match($build, $logger);
        self::assertTrue($matched, 'patch matched');
        $patchedCount = $patch->apply($build, $logger);

        $sourceExpectedDirectory = getenv('PHPBREW_EXPECTED_PHP_DIR') . DIRECTORY_SEPARATOR . $version . '-apxs-patch';
        self::assertEquals($expectedPatchedCount, $patchedCount);
        self::assertFileEquals($sourceExpectedDirectory . $makefile, $sourceDirectory . $makefile);
        self::assertFileEquals($sourceExpectedDirectory . '/configure', $sourceDirectory . '/configure');
    }
}
