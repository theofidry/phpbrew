<?php

namespace PhpBrew\Command;

use PhpBrew\BuildFinder;

/**
 * @codeCoverageIgnore
 */
class PurgeCommand extends VirtualCommand
{
    public function arguments($args): void
    {
        $args->add('PHP build')
            ->validValues(static function () {
                return BuildFinder::findInstalledBuilds();
            })
            ->multiple();
    }

    public function brief()
    {
        return 'Remove installed php version and config files.';
    }
}
