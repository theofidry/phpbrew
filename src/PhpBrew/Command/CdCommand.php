<?php

declare(strict_types=1);

namespace PhpBrew\Command;

/**
 * @codeCoverageIgnore
 */
class CdCommand extends VirtualCommand
{
    public function brief()
    {
        return 'Change to directories';
    }

    public function arguments($args): void
    {
        $args->add('directory')
            ->isa('string')
            ->validValues(explode('|', 'var|etc|build|dist'));
    }

    public function usage()
    {
        return 'phpbrew cd [var|etc|build|dist]';
    }
}
