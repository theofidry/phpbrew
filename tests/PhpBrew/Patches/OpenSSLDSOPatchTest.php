<?php

declare(strict_types=1);

namespace PhpBrew\Tests\Patches;

use CLIFramework\Logger;
use PhpBrew\Build;
use PhpBrew\Patches\OpenSSLDSOPatch;
use PhpBrew\Testing\PatchTestCase;

/**
 * @small
 * @internal
 */
class OpenSSLDSOPatchTest extends PatchTestCase
{
    public function test_patch(): void
    {
        if (PHP_OS !== 'Darwin') {
            self::markTestSkipped('openssl DSO patch test only runs on darwin platform');
        }

        $logger = new Logger();
        $logger->setQuiet();

        $fromVersion = '5.5.17';
        $sourceDirectory = getenv('PHPBREW_BUILD_PHP_DIR');

        $this->setupBuildDirectory($fromVersion);

        $build = new Build($fromVersion);
        $build->setSourceDirectory($sourceDirectory);
        $build->enableVariant('openssl');
        self::assertTrue($build->isEnabledVariant('openssl'));

        $patch = new OpenSSLDSOPatch();
        $matched = $patch->match($build, $logger);
        self::assertTrue($matched, 'patch matched');
        $patchedCount = $patch->apply($build, $logger);
        self::assertEquals(10, $patchedCount);
    }
}
