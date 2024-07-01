<?php

namespace PhpBrew\Tests\Command;

use PhpBrew\Testing\CommandTestCase;

/**
 * @large
 * @group command
 * @internal
 */
class ExtensionCommandTest extends CommandTestCase
{
    public static function extensionNameProvider(): iterable
    {
        return [
            [
                'APCu',
                'latest',
            ],
            [
                'xdebug',
                'latest',
            ],
        ];
    }

    /**
     * @outputBuffering enabled
     * @dataProvider extensionNameProvider
     * @param mixed $extensionName
     * @param mixed $extensionVersion
     */
    public function test_ext_install_command($extensionName, $extensionVersion): void
    {
        self::markTestSkipped('This test can not be run against system php');
        self::assertTrue($this->runCommandWithStdout("phpbrew ext install {$extensionName} {$extensionVersion}"));
    }

    /**
     * @outputBuffering enabled
     * @dataProvider extensionNameProvider
     * @depends test_ext_install_command
     * @param mixed $extensionName
     * @param mixed $extensionVersion
     */
    public function test_ext_show_command($extensionName, $extensionVersion): void
    {
        self::assertTrue($this->runCommandWithStdout("phpbrew ext show {$extensionName}"));
    }

    /**
     * @outputBuffering enabled
     * @dataProvider extensionNameProvider
     * @depends test_ext_install_command
     * @param mixed $extensionName
     * @param mixed $extensionVersion
     */
    public function test_ext_clean_command($extensionName, $extensionVersion): void
    {
        self::assertTrue($this->runCommandWithStdout("phpbrew ext clean {$extensionName}"));
    }

    /**
     * @outputBuffering enabled
     * @depends test_ext_install_command
     */
    public function test_ext_list_command(): void
    {
        self::assertTrue($this->runCommandWithStdout('phpbrew ext --show-path --show-options'));
    }
}
