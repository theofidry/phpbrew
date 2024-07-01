<?php

namespace PhpBrew\Tests\Command;

use PhpBrew\Testing\CommandTestCase;

/**
 * @large
 * @group command
 */
class PathCommandTest extends CommandTestCase
{

    public function argumentsProvider()
    {
        return [
            [
                "build",
                "#\.phpbrew/build/.+#",
            ],
            [
                "ext-src",
                "#\.phpbrew/build/.+/ext$#",
            ],
            [
                "include",
                "#\.phpbrew/php/.+/include$#",
            ],
            [
                "etc",
                "#\.phpbrew/php/.+/etc$#",
            ],
            [
                "dist",
                "#\.phpbrew/distfiles$#",
            ],
            [
                "root",
                "#\.phpbrew$#",
            ],
            [
                "home",
                "#\.phpbrew$#",
            ],
        ];
    }

    /**
     * @outputBuffering enabled
     * @dataProvider argumentsProvider
     */
    public function testPathCommand($arg, $pattern)
    {
        putenv('PHPBREW_PHP=7.4.0');

        ob_start();
        $this->runCommandWithStdout("phpbrew path $arg");
        $path = ob_get_clean();
        $this->assertRegExp($pattern, $path);
    }
}
