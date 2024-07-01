<?php

declare(strict_types=1);

namespace PhpBrew\Tests;

use PhpBrew\ReleaseList;
use PHPUnit\Framework\TestCase;

/**
 * @small
 * @internal
 */
class ReleaseListTest extends TestCase
{
    public $releaseList;

    protected function setUp(): void
    {
        $this->releaseList = new ReleaseList();
        $this->releaseList->loadJsonFile(__DIR__ . '/../fixtures/php-releases.json');
    }

    public function test_get_versions(): void
    {
        $versions = $this->releaseList->getVersions('7.2');
        self::assertSame(
            $versions['7.2.0'],
            [
                'version' => '7.2.0',
                'announcement' => 'https://php.net/releases/7_2_0.php',
                'date' => '30 Nov 2017',
                'filename' => 'php-7.2.0.tar.bz2',
                'name' => 'PHP 7.2.0 (tar.bz2)',
                'sha256' => '2bfefae4226b9b97879c9d33078e50bdb5c17f45ff6e255951062a529720c64a',
                'museum' => false,
            ]
        );
    }

    public static function versionDataProvider(): iterable
    {
        return [
            [
                '7.3',
                '7.3.0',
            ],
            [
                '7.2',
                '7.2.13',
            ],
            [
                '5.4',
                '5.4.45',
            ],
            [
                '5.6',
                '5.6.39',
            ],
        ];
    }

    /**
     * @dataProvider versionDataProvider
     * @param mixed $major
     * @param mixed $minor
     */
    public function test_latest_patch_version($major, $minor): void
    {
        $version = $this->releaseList->getLatestPatchVersion($major, $minor);
        self::assertInternalType('array', $version);
        self::assertEquals($version['version'], $minor);
    }

    /**
     * @dataProvider versionDataProvider
     * @param mixed $major
     * @param mixed $minor
     */
    public function test_get_latest_version($major, $minor): void
    {
        $latestVersion = $this->releaseList->getLatestVersion();

        self::assertNotNull($latestVersion);

        $versions = $this->releaseList->getVersions($major);

        foreach ($versions as $versionInfo) {
            self::assertTrue($latestVersion >= $versionInfo['version']);
        }
    }
}
