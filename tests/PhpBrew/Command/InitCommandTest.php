<?php

declare(strict_types=1);

namespace PhpBrew\Tests\Command;

use PhpBrew\Testing\CommandTestCase;

/**
 * @large
 * @group command
 * @internal
 */
class InitCommandTest extends CommandTestCase
{
    /**
     * @outputBuffering enabled
     */
    public function test_init_command(): void
    {
        ob_start();
        self::assertTrue($this->runCommand('phpbrew init'));
        ob_end_clean();
    }
}
