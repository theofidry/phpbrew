<?php

declare(strict_types=1);

namespace PhpBrew\Tests\Command;

use PhpBrew\Testing\CommandTestCase;

/**
 * @large
 * @group command
 * @internal
 */
class KnownCommandTest extends CommandTestCase
{
    public $usesVCR = true;

    /**
     * @outputBuffering enabled
     * @group mayignore
     */
    public function test_command(): void
    {
        $this->assertCommandSuccess('phpbrew --quiet known');
    }

    /**
     * @outputBuffering enabled
     * @group mayignore
     */
    public function test_more_option(): void
    {
        $this->assertCommandSuccess('phpbrew --quiet known --more');
    }

    /**
     * @outputBuffering enabled
     * @group mayignore
     */
    public function test_old_more_option(): void
    {
        $this->assertCommandSuccess('phpbrew --quiet known --old --more');
    }

    /**
     * @outputBuffering enabled
     * @group mayignore
     */
    public function test_known_update_command(): void
    {
        $this->assertCommandSuccess('phpbrew --quiet known --update');
        $this->assertCommandSuccess('phpbrew --quiet known -u');
    }
}
