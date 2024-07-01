<?php

declare(strict_types=1);

namespace PhpBrew\Tasks;

use PhpBrew\Build;

class DSymTask extends BaseTask
{
    // Fix php.dSYM
    /* Check if php.dSYM exists */
    /**
     * @return bool
     */
    public function check(Build $build)
    {
        $phpbin = $build->getBinDirectory() . DIRECTORY_SEPARATOR . 'php';
        $dSYM = $build->getBinDirectory() . DIRECTORY_SEPARATOR . 'php.dSYM';

        return !file_exists($phpbin) && file_exists($dSYM);
    }

    public function patch(Build $build, $options): void
    {
        if ($this->check($build)) {
            $this->logger->info('---> Moving php.dSYM to php ');
            if (!$options->dryrun) {
                $phpBin = $build->getBinDirectory() . DIRECTORY_SEPARATOR . 'php';
                $dSYM = $build->getBinDirectory() . DIRECTORY_SEPARATOR . 'php.dSYM';
                rename($dSYM, $phpBin);
            }
        }
    }
}
