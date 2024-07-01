<?php

namespace PhpBrew\Tests\Patches;

use CLIFramework\Logger;
use PhpBrew\Build;
use PhpBrew\Patches\PHP56WithOpenSSL11Patch;
use PhpBrew\Testing\PatchTestCase;

/**
 * @internal
 */
class PHP56WithOpenSSL11PatchTest extends PatchTestCase
{
    /**
     * @dataProvider versionProvider
     * @param mixed $version
     */
    public function test_patch_version($version): void
    {
        $logger = new Logger();
        $logger->setQuiet();

        $sourceDirectory = getenv('PHPBREW_BUILD_PHP_DIR');

        $this->setupBuildDirectory($version);

        $build = new Build($version);
        $build->setSourceDirectory($sourceDirectory);
        $build->enableVariant('openssl');
        self::assertTrue($build->isEnabledVariant('openssl'));

        $patch = new PHP56WithOpenSSL11Patch();
        self::assertTrue($patch->match($build, $logger));

        self::assertGreaterThan(0, $patch->apply($build, $logger));

        $expectedDirectory = getenv('PHPBREW_EXPECTED_PHP_DIR') . '/' . $version . '-php56-openssl11-patch';

        foreach (
            [
                'ext/openssl/openssl.c',
                'ext/openssl/xp_ssl.c',
                'ext/phar/util.c',
            ] as $path
        ) {
            self::assertFileEquals(
                $expectedDirectory . '/' .  $path,
                $sourceDirectory . '/' . $path
            );
        }
    }

    public static function versionProvider(): iterable
    {
        return [['5.6.40']];
    }
}
