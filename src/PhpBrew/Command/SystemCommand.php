<?php

declare(strict_types=1);

namespace PhpBrew\Command;

use CLIFramework\Command;
use PhpBrew\BuildFinder;

class SystemCommand extends Command
{
    public function brief()
    {
        return 'Get or set the internally used PHP binary';
    }

    public function arguments($args): void
    {
        $args->add('php version')
            ->suggestions(static function () {
                return BuildFinder::findInstalledBuilds();
            });
    }

    final public function execute(): void
    {
        $path = getenv('PHPBREW_SYSTEM_PHP');

        if ($path !== false && $path !== '') {
            $this->logger->writeln($path);
        }
    }
}
