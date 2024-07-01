<?php

declare(strict_types=1);

namespace PhpBrew\Command;

use CLIFramework\Command;
use Exception;

/**
 * @codeCoverageIgnore
 */
class VirtualCommand extends Command
{
    /**
     * @throws Exception
     */
    final public function execute(): void
    {
        throw new Exception(
            'You should not see this. '
            . "If you see this, it means you didn't load the ~/.phpbrew/bashrc script. "
            . 'Please check if bashrc is sourced in your shell.'
        );
    }
}
