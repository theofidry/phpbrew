<?php

declare(strict_types=1);

namespace PhpBrew\Command;

/**
 * @codeCoverageIgnore
 */
class OffCommand extends VirtualCommand
{
    public function brief()
    {
        return 'Temporarily go back to the system php';
    }
}
