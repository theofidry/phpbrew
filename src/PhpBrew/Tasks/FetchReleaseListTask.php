<?php

declare(strict_types=1);

namespace PhpBrew\Tasks;

use PhpBrew\ReleaseList;

class FetchReleaseListTask extends BaseTask
{
    public function fetch()
    {
        $this->logger->info('===> Fetching release list...');
        $releaseList = new ReleaseList();
        $releaseList->fetchRemoteReleaseList($this->options);

        return $releaseList->getReleases();
    }
}
