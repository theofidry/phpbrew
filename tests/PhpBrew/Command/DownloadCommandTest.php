<?php

namespace PhpBrew\Tests\Command;

use PhpBrew\Testing\CommandTestCase;

/**
 * @large
 * @group command
 * @group noVCR
 */
class DownloadCommandTest extends CommandTestCase
{

    public function versionDataProvider()
    {
        return [['7.0'], ['7.0.33']];
    }

    /**
     * @outputBuffering enabled
     * @dataProvider versionDataProvider
     */
    public function testDownloadCommand($versionName)
    {
        if (getenv('GITHUB_ACTIONS')) {
            $this->markTestSkipped('Skip heavy test on Travis');
        }

        $this->assertCommandSuccess("phpbrew init");
        $this->assertCommandSuccess("phpbrew -q download $versionName");

        // re-download should just check the checksum instead of extracting it
        $this->assertCommandSuccess("phpbrew -q download $versionName");
        $this->assertCommandSuccess("phpbrew -q download -f $versionName");
    }
}
