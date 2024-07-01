<?php

namespace PhpBrew\Command;

use CLIFramework\Command;

/**
 * @codeCoverageIgnore
 */
class MigratedCommand extends Command
{
    public function brief()
    {
        return 'This command is migrated';
    }

    public function execute(): void
    {
        echo <<<'HELP'
- `phpbrew install-ext` command is now moved to `phpbrew ext install`
- `phpbrew enable` command is now moved to `phpbrew ext enable`
HELP;
    }
}
