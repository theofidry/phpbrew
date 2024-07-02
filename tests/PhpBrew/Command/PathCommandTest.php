<?php

namespace PhpBrew\Tests\Command;

use PhpBrew\Testing\CommandTestCase;

/**
 * @large
 * @group command
 * @internal
 */
class PathCommandTest extends CommandTestCase
{
    public static function argumentsProvider(): iterable
    {
        return [
            [
                'build',
                '#\.phpbrew/build/.+#',
            ],
            [
                'ext-src',
                '#\.phpbrew/build/.+/ext$#',
            ],
            [
                'include',
                '#\.phpbrew/php/.+/include$#',
            ],
            [
                'etc',
                '#\.phpbrew/php/.+/etc$#',
            ],
            [
                'dist',
                '#\.phpbrew/distfiles$#',
            ],
            [
                'root',
                '#\.phpbrew$#',
            ],
            [
                'home',
                '#\.phpbrew$#',
            ],
        ];
    }

    /**
     * @outputBuffering enabled
     * @dataProvider argumentsProvider
     * @param mixed $arg
     * @param mixed $pattern
     */
    public function test_path_command($arg, $pattern): void
    {
        putenv('PHPBREW_PHP=7.4.0');

        ob_start();
        $this->runCommandWithStdout("phpbrew path {$arg}");
        $path = ob_get_clean();
        self::assertRegExp($pattern, $path);
    }
}
