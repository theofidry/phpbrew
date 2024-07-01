<?php

declare(strict_types=1);

namespace PhpBrew\Tests\Command;

use PhpBrew\Testing\CommandTestCase;

/**
 * @large
 * @group command
 * @internal
 */
class InfoCommandTest extends CommandTestCase
{
    /**
     * @outputBuffering enabled
     */
    public function test_info_command(): void
    {
        $this->assertCommandSuccess('phpbrew info');
    }
}
