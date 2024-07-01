<?php

namespace PhpBrew\Tests\BuildSettings;

use PhpBrew\BuildSettings\BuildSettings;
use PHPUnit\Framework\TestCase;

class BuildSettingsTest extends TestCase
{
    public function testConstructorWithEnabledVariants()
    {
        $settings = new BuildSettings([
            'enabled_variants' => ['sqlite' => null],
        ]);

        $this->assertTrue($settings->isEnabledVariant('sqlite'));
    }

    public function testConstructorWithDisabledVariants()
    {
        $settings = new BuildSettings([
            'disabled_variants' => ['sqlite' => true],
        ]);

        $this->assertFalse($settings->isEnabledVariant('sqlite'));
    }

    public function testToArray()
    {
        $enabledVariants = ['sqlite' => null, 'curl' => 'yes'];
        $disabledVariants = ['dom' => null];
        $extraOptions = [];
        $settings = new BuildSettings([
            'enabled_variants'  => $enabledVariants,
            'disabled_variants' => $disabledVariants,
            'extra_options'     => $extraOptions,
        ]);

        $expected = [
            'enabled_variants'  => $enabledVariants,
            'disabled_variants' => $disabledVariants,
            'extra_options'     => $extraOptions,
        ];
        $this->assertEquals($expected, $settings->toArray());
    }

    public function testEnableVariant()
    {
        $settings = new BuildSettings();
        $settings->enableVariant('curl');

        $this->assertTrue($settings->isEnabledVariant('curl'));
    }

    public function testEnableVariants()
    {
        $variants = [
            'sqlite' => null,
            'curl'   => 'yes',
            'dom'    => null,
        ];
        $settings = new BuildSettings();
        $settings->enableVariants($variants);

        $this->assertEquals($variants, $settings->getEnabledVariants());
    }

    public function testDisableVariants()
    {
        $variants = [
            'sqlite' => null, 'curl'   => 'yes', 'dom'    => null];
        $settings = new BuildSettings();
        $settings->disableVariants($variants);

        $expected = [
            'sqlite' => null,
            'curl'   => null,
            'dom'    => null,
        ];
        $this->assertEquals($expected, $settings->getDisabledVariants());
    }

    public function testIsEnabledVariant()
    {
        $settings = new BuildSettings();
        $settings->enableVariant('sqlite');
        $settings->disableVariant('curl');

        $this->assertTrue($settings->isEnabledVariant('sqlite'));
        $this->assertFalse($settings->isEnabledVariant('curl'));
    }

    public function testRemoveVariant()
    {
        $settings = new BuildSettings();
        $settings->enableVariant('sqlite');

        $this->assertTrue($settings->isEnabledVariant('sqlite'));
        $settings->removeVariant('sqlite');
        $this->assertFalse($settings->isEnabledVariant('sqlite'));
    }

    public function testResolveVariants()
    {
        $settings = new BuildSettings();
        $settings->enableVariant('sqlite');
        $settings->disableVariant('sqlite');
        $settings->resolveVariants();

        $this->assertEquals([], $settings->getEnabledVariants());
    }

    public function testGetVariants()
    {
        $settings = new BuildSettings();
        $settings->enableVariant('sqlite');
        $settings->enableVariant('curl');
        $settings->disableVariant('dom');

        $this->assertEquals(['sqlite' => null, 'curl' => null], $settings->getEnabledVariants());
    }

    public function testGetDisabledVariants()
    {
        $settings = new BuildSettings();
        $settings->enableVariant('sqlite');
        $settings->enableVariant('curl');
        $settings->disableVariant('dom');

        $this->assertEquals(['dom' => null], $settings->getDisabledVariants());
    }
}
