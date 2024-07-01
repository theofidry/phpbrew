<?php

declare(strict_types=1);

namespace PhpBrew\Distribution;

class DistributionUrlPolicy
{
    /**
     * Returns the distribution url for the version.
     * @param mixed $version
     * @param mixed $filename
     * @param mixed $museum
     */
    public function buildUrl($version, $filename, $museum = false)
    {
        // the historic releases only available at museum
        if ($museum || $this->isDistributedAtMuseum($version)) {
            return 'https://museum.php.net/php5/' . $filename;
        }

        return 'https://www.php.net/distributions/' . $filename;
    }

    private function isDistributedAtMuseum($version)
    {
        return version_compare($version, '5.4.21', '<=');
    }
}
