<?php

declare(strict_types=1);

namespace PhpBrew\Tests\Command;

use PhpBrew\Testing\CommandTestCase;

/**
 * @large
 * @group command
 * @internal
 */
class ListCommandTest extends CommandTestCase
{
    /**
     * @outputBuffering enabled
     */
    public function test_list_command(): void
    {
        $this->assertCommandSuccess('phpbrew list');
    }
}
