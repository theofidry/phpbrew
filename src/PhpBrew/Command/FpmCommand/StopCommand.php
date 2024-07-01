<?php

declare(strict_types=1);

namespace PhpBrew\Command\FpmCommand;

use PhpBrew\Command\VirtualCommand;

class StopCommand extends VirtualCommand
{
    public function brief()
    {
        return 'Stop FPM server';
    }
}
