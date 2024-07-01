<?php

declare(strict_types=1);

namespace PhpBrew\Tests\Extension;

use CLIFramework\Logger;
use GetOptionKit\OptionResult;
use PhpBrew\Extension\ExtensionDownloader;
use PhpBrew\Extension\ExtensionFactory;
use PhpBrew\Extension\ExtensionManager;
use PhpBrew\Extension\Provider\PeclProvider;
use PhpBrew\Testing\CommandTestCase;

/**
 * NOTE: This depends on an existing installed php build. we need to ensure
 * that the installer test runs before this test.
 *
 * @large
 * @group extension
 * @internal
 */
class ExtensionInstallerTest extends CommandTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $versionName = $this->getPrimaryVersion();
        $this->runCommand("phpbrew use php-{$versionName}");
    }

    /**
     * @group noVCR
     */
    public function test_package_url(): void
    {
        if (getenv('GITHUB_ACTIONS')) {
            self::markTestSkipped('Skipping since VCR cannot properly record this request');
        }

        $logger = new Logger();
        $logger->setQuiet();
        $peclProvider = new PeclProvider($logger, new OptionResult());
        $downloader = new ExtensionDownloader($logger, new OptionResult());
        $peclProvider->setPackageName('APCu');
        $extractPath = $downloader->download($peclProvider, 'latest');
        self::assertFileExists($extractPath);
    }

    public static function packageNameProvider(): iterable
    {
        return [
            // xdebug requires at least php 5.4
            // array('xdebug'),
            [version_compare(PHP_VERSION, '5.5', '=='), 'APCu', 'stable', []],
        ];
    }

    /**
     * @dataProvider packageNameProvider
     * @param mixed $build
     * @param mixed $extensionName
     * @param mixed $extensionVersion
     * @param mixed $options
     */
    public function test_install_packages($build, $extensionName, $extensionVersion, $options): void
    {
        if (!$build) {
            self::markTestSkipped('skip extension build test');

            return;
        }
        $logger = new Logger();
        $logger->setDebug();
        $manager = new ExtensionManager($logger);
        $peclProvider = new PeclProvider();
        $downloader = new ExtensionDownloader($logger, new OptionResult());
        $peclProvider->setPackageName($extensionName);
        $downloader->download($peclProvider, $extensionVersion);
        $ext = ExtensionFactory::lookup($extensionName);
        self::assertNotNull($ext);
        $manager->installExtension($ext, $options);
    }
}
