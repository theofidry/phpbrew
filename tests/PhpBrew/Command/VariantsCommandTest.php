<?php

namespace PhpBrew\Tests\Command;

use PhpBrew\Testing\CommandTestCase;

/**
 * @large
 * @group command
 * @internal
 */
class VariantsCommandTest extends CommandTestCase
{
    /**
     * @outputBuffering enabled
     */
    public function test_variants_command(): void
    {
        $this->assertCommandSuccess('phpbrew variants');
    }
}
