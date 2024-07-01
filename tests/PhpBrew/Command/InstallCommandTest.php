<?php

declare(strict_types=1);

namespace PhpBrew\Tests\Command;

use PhpBrew\BuildFinder;
use PhpBrew\Testing\CommandTestCase;

/**
 * The install command tests are heavy.
 *
 * Don't catch the exceptions, the system command exception
 * will show up the error message.
 *
 * Build output will be shown when assertion failed.
 *
 * @large
 * @group command
 * @group noVCR
 * @internal
 */
class InstallCommandTest extends CommandTestCase
{
    public $usesVCR = false;

    /**
     * @group install
     * @group mayignore
     */
    public function test_install_command(): void
    {
        if (getenv('GITHUB_ACTIONS')) {
            self::markTestSkipped('Skip heavy test on Travis');
        }

        $this->assertCommandSuccess('phpbrew init');
        $this->assertCommandSuccess('phpbrew known --update');

        $versionName = $this->getPrimaryVersion();
        $this->assertCommandSuccess("phpbrew install php-{$versionName} +cli+posix+intl+gd");
        $this->assertListContains("php-{$versionName}");
    }

    /**
     * @depends test_install_command
     */
    public function test_env_command(): void
    {
        $versionName = $this->getPrimaryVersion();
        $this->assertCommandSuccess("phpbrew env php-{$versionName}");
    }

    /**
     * @depends test_install_command
     * @group mayignore
     */
    public function test_ctags_command(): void
    {
        $versionName = $this->getPrimaryVersion();
        $this->assertCommandSuccess("phpbrew ctags php-{$versionName}");
    }

    /**
     * @group install
     * @group mayignore
     */
    public function test_git_hub_install_command(): void
    {
        if (getenv('GITHUB_ACTIONS')) {
            self::markTestSkipped('Skip heavy test on Travis');
        }

        $this->assertCommandSuccess(
            'phpbrew --debug install --dryrun github:php/php-src@PHP-7.0 as php-7.0.0 +cli+posix'
        );
    }

    /**
     * @depends test_install_command
     * @group install
     */
    public function test_install_as_command(): void
    {
        $versionName = $this->getPrimaryVersion();
        $this->assertCommandSuccess("phpbrew install php-{$versionName} as myphp +cli+soap");
        $this->assertListContains('myphp');
    }

    /**
     * @depends test_install_command
     */
    public function test_clean_command(): void
    {
        $versionName = $this->getPrimaryVersion();
        $this->assertCommandSuccess("phpbrew clean php-{$versionName}");
    }

    protected function assertListContains($string): void
    {
        self::assertContains($string, BuildFinder::findInstalledBuilds());
    }
}
