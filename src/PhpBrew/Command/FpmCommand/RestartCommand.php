<?php

declare(strict_types=1);

namespace PhpBrew\Command\FpmCommand;

use PhpBrew\Command\VirtualCommand;

class RestartCommand extends VirtualCommand
{
    public function brief()
    {
        return 'Restart FPM server';
    }
}
