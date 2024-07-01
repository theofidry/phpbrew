<?php

declare(strict_types=1);

namespace PhpBrew\Tests\Command;

use PhpBrew\Console;
use PhpBrew\Testing\CommandTestCase;

/**
 * @group command
 * @internal
 */
class EnvCommandTest extends CommandTestCase
{
    public function setupApplication()
    {
        return new Console();
    }

    protected function setUp(): void
    {
        parent::setUp();
        putenv('PHPBREW_HOME=' . getcwd() . '/.phpbrew');
        putenv('PHPBREW_ROOT=' . getcwd() . '/.phpbrew');
    }

    /**
     * @outputBuffering enabled
     */
    public function test_env_command(): void
    {
        $this->assertCommandSuccess('phpbrew env');
    }
}
