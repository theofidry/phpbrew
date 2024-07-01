<?php

declare(strict_types=1);

namespace PhpBrew\Tasks;

use PhpBrew\Build;
use PhpBrew\Exception\SystemCommandException;

/**
 * Task to run `./buildconf`.
 */
class BuildConfTask extends BaseTask
{
    public function run(Build $build): void
    {
        $lastLine = system('./buildconf --force', $status);

        if ($status !== 0) {
            throw new SystemCommandException(
                sprintf('buildconf error: %s', $lastLine),
                $build
            );
        }
    }
}
