<?php

namespace PhpBrew\Tests;

use PhpBrew\CommandBuilder;
use PHPUnit\Framework\TestCase;

/**
 * @small
 * @internal
 */
class CommandBuilderTest extends TestCase
{
    public function test(): void
    {
        ob_start();
        $cmd = new CommandBuilder('ls');
        self::assertEquals(0, $cmd->execute());
        ob_end_clean();
    }

    /**
     * @dataProvider provideTestGetCommandTestCases
     * @param mixed $appendLog
     * @param mixed $stdout
     * @param mixed $logPath
     * @param mixed $expected
     */
    public function test_get_command($appendLog, $stdout, $logPath, $expected): void
    {
        $cmd = new CommandBuilder('ls');
        $cmd->setAppendLog($appendLog);
        $cmd->setStdout($stdout);
        $cmd->setLogPath($logPath);
        self::assertEquals($expected, $cmd->buildCommand());
        ob_start();
        self::assertEquals(0, $cmd->execute());
        ob_end_clean();
    }

    public static function provideTestGetCommandTestCases(): iterable
    {
        return [
            [
                'appendLog' => true,
                'stdout' => true,
                'logPath' => '/tmp/build.log',
                'expected' => 'ls 2>&1',
            ],
            [
                'appendLog' => false,
                'stdout' => true,
                'logPath' => '/tmp/build.log',
                'expected' => 'ls 2>&1',
            ],
            [
                'appendLog' => true,
                'stdout' => false,
                'logPath' => '/tmp/build.log',
                'expected' => "ls >> '/tmp/build.log' 2>&1",
            ],
            [
                'appendLog' => false,
                'stdout' => false,
                'logPath' => '/tmp/build with whitespaces.log',
                'expected' => "ls > '/tmp/build with whitespaces.log' 2>&1",
            ],
            [
                'appendLog' => true,
                'stdout' => false,
                'logPath' => null,
                'expected' => 'ls',
            ],
            [
                'appendLog' => false,
                'stdout' => false,
                'logPath' => null,
                'expected' => 'ls',
            ],
            [
                'appendLog' => true,
                'stdout' => false,
                'logPath' => null,
                'expected' => 'ls',
            ],
            [
                'appendLog' => false,
                'stdout' => false,
                'logPath' => null,
                'expected' => 'ls',
            ],
        ];
    }
}
