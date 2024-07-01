<?php

declare(strict_types=1);

namespace PhpBrew\PatchKit;

use CLIFramework\Logger;
use PhpBrew\Buildable;

interface PatchRule
{
    public function apply(Buildable $build, Logger $logger);

    public function backup(Buildable $build, Logger $logger);
}
