<?php

namespace PhpBrew\Tests;

use PhpBrew\ConfigureParameters;
use PHPUnit\Framework\TestCase;

final class ConfigureParametersTest extends TestCase
{
    private $configureParameters;

    protected function setUp(): void
    {
        $this->configureParameters = new ConfigureParameters();
    }

    public function testDefaults()
    {
        $this->assertSame([], $this->configureParameters->getOptions());
        $this->assertSame([], $this->configureParameters->getPkgConfigPaths());
    }

    public function testWithOptionAndValue()
    {
        $this->assertSame(['--with-foo' => 'bar'], $this->configureParameters
            ->withOption('--with-foo', 'bar')
            ->getOptions());
    }

    public function testWithOptionAndNoValue()
    {
        $this->assertSame(['--with-foo' => null], $this->configureParameters
            ->withOption('--with-foo')
            ->getOptions());
    }

    public function testWithSameOptionAndValue()
    {
        $this->assertSame(['--with-foo' => 'bar'], $this->configureParameters
            ->withOption('--with-foo', 'bar')
            ->withOption('--with-foo', 'bar')
            ->getOptions());
    }

    public function testWithPkgConfigPath()
    {
        $this->assertSame(['/usr/lib/pkgconfig'], $this->configureParameters
            ->withPkgConfigPath('/usr/lib/pkgconfig')
            ->getPkgConfigPaths());
    }

    public function testWithSamePkgConfigPath()
    {
        $this->assertSame(['/usr/lib/pkgconfig'], $this->configureParameters
            ->withPkgConfigPath('/usr/lib/pkgconfig')
            ->withPkgConfigPath('/usr/lib/pkgconfig')
            ->getPkgConfigPaths());
    }
}
