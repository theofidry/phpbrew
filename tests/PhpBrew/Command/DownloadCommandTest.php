<?php

declare(strict_types=1);

namespace PhpBrew\Tests\Command;

use PhpBrew\Testing\CommandTestCase;

/**
 * @large
 * @group command
 * @group noVCR
 * @internal
 */
class DownloadCommandTest extends CommandTestCase
{
    public static function versionDataProvider(): iterable
    {
        return [
            ['7.0'],
            ['7.0.33'],
        ];
    }

    /**
     * @outputBuffering enabled
     * @dataProvider versionDataProvider
     * @param mixed $versionName
     */
    public function test_download_command($versionName): void
    {
        if (getenv('GITHUB_ACTIONS')) {
            self::markTestSkipped('Skip heavy test on Travis');
        }

        $this->assertCommandSuccess('phpbrew init');
        $this->assertCommandSuccess("phpbrew -q download {$versionName}");

        // re-download should just check the checksum instead of extracting it
        $this->assertCommandSuccess("phpbrew -q download {$versionName}");
        $this->assertCommandSuccess("phpbrew -q download -f {$versionName}");
    }
}
