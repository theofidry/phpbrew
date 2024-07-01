<?php

declare(strict_types=1);

namespace PhpBrew\Tests;

use PhpBrew\ConfigureParameters;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class ConfigureParametersTest extends TestCase
{
    private $configureParameters;

    protected function setUp(): void
    {
        $this->configureParameters = new ConfigureParameters();
    }

    public function test_defaults(): void
    {
        self::assertSame([], $this->configureParameters->getOptions());
        self::assertSame([], $this->configureParameters->getPkgConfigPaths());
    }

    public function test_with_option_and_value(): void
    {
        self::assertSame(['--with-foo' => 'bar'], $this->configureParameters
            ->withOption('--with-foo', 'bar')
            ->getOptions());
    }

    public function test_with_option_and_no_value(): void
    {
        self::assertSame(['--with-foo' => null], $this->configureParameters
            ->withOption('--with-foo')
            ->getOptions());
    }

    public function test_with_same_option_and_value(): void
    {
        self::assertSame(['--with-foo' => 'bar'], $this->configureParameters
            ->withOption('--with-foo', 'bar')
            ->withOption('--with-foo', 'bar')
            ->getOptions());
    }

    public function test_with_pkg_config_path(): void
    {
        self::assertSame(['/usr/lib/pkgconfig'], $this->configureParameters
            ->withPkgConfigPath('/usr/lib/pkgconfig')
            ->getPkgConfigPaths());
    }

    public function test_with_same_pkg_config_path(): void
    {
        self::assertSame(['/usr/lib/pkgconfig'], $this->configureParameters
            ->withPkgConfigPath('/usr/lib/pkgconfig')
            ->withPkgConfigPath('/usr/lib/pkgconfig')
            ->getPkgConfigPaths());
    }
}
