<?php

declare(strict_types=1);

namespace PhpBrew\Command;

use PhpBrew\BuildFinder;

/**
 * @codeCoverageIgnore
 */
class UseCommand extends VirtualCommand
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
        return 'Use php, switch version temporarily';
    }
}
