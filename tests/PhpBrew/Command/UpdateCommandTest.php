<?php

declare(strict_types=1);

namespace PhpBrew\Tests\Command;

use PhpBrew\Testing\CommandTestCase;

/**
 * @large
 * @group command
 * @internal
 */
class UpdateCommandTest extends CommandTestCase
{
    public $usesVCR = true;

    /**
     * @outputBuffering enabled
     * @group mayignore
     */
    public function test_update_command(): void
    {
        $this->assertCommandSuccess('phpbrew --quiet update --old');
    }
}
