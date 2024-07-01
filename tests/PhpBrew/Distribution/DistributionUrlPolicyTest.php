<?php

namespace PhpBrew\Tests\Distribution;

use PhpBrew\Distribution\DistributionUrlPolicy;
use PHPUnit\Framework\TestCase;

/**
 * @small
 * @internal
 */
class DistributionUrlPolicyTest extends TestCase
{
    private $policy;

    protected function setUp(): void
    {
        $this->policy = new DistributionUrlPolicy();
    }

    /**
     * @dataProvider versionDataProvider
     * @param mixed $version
     * @param mixed $filename
     * @param mixed $distUrl
     */
    public function test_build_url($version, $filename, $distUrl): void
    {
        self::assertSame(
            $distUrl,
            $this->policy->buildUrl($version, $filename)
        );
    }

    public static function versionDataProvider(): iterable
    {
        return [['5.3.29', 'php-5.3.29.tar.bz2', 'https://museum.php.net/php5/php-5.3.29.tar.bz2'], ['5.4.7', 'php-5.4.7.tar.bz2', 'https://museum.php.net/php5/php-5.4.7.tar.bz2'], ['5.4.21', 'php-5.4.21.tar.bz2', 'https://museum.php.net/php5/php-5.4.21.tar.bz2'], ['5.4.22', 'php-5.4.22.tar.bz2', 'https://www.php.net/distributions/php-5.4.22.tar.bz2'], ['5.6.23', 'php-5.6.23.tar.bz2', 'https://www.php.net/distributions/php-5.6.23.tar.bz2']];
    }
}
