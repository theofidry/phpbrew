<?php

declare(strict_types=1);

namespace PhpBrew\Command;

use PhpBrew\BuildFinder;

/**
 * @codeCoverageIgnore
 */
class SwitchCommand extends VirtualCommand
{
    public function arguments($args): void
    {
        $args->add('PHP version')
            ->validValues(static function () {
                return BuildFinder::findInstalledVersions();
            });
    }

    public function brief()
    {
        return 'Switch default php version.';
    }
}
