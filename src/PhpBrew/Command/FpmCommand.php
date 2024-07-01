<?php

declare(strict_types=1);

namespace PhpBrew\Command;

class FpmCommand extends VirtualCommand
{
    public function brief()
    {
        return 'fpm commands';
    }

    public function init(): void
    {
        parent::init();

        $this->command('restart');
        $this->command('setup');
        $this->command('start');
        $this->command('stop');
    }
}
