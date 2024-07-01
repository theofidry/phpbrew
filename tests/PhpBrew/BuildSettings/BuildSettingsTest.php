<?php

declare(strict_types=1);

namespace PhpBrew\Tests\BuildSettings;

use PhpBrew\BuildSettings\BuildSettings;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class BuildSettingsTest extends TestCase
{
    public function test_constructor_with_enabled_variants(): void
    {
        $settings = new BuildSettings([
            'enabled_variants' => ['sqlite' => null],
        ]);

        self::assertTrue($settings->isEnabledVariant('sqlite'));
    }

    public function test_constructor_with_disabled_variants(): void
    {
        $settings = new BuildSettings([
            'disabled_variants' => ['sqlite' => true],
        ]);

        self::assertFalse($settings->isEnabledVariant('sqlite'));
    }

    public function test_to_array(): void
    {
        $enabledVariants = ['sqlite' => null, 'curl' => 'yes'];
        $disabledVariants = ['dom' => null];
        $extraOptions = [];
        $settings = new BuildSettings([
            'enabled_variants' => $enabledVariants,
            'disabled_variants' => $disabledVariants,
            'extra_options' => $extraOptions,
        ]);

        $expected = [
            'enabled_variants' => $enabledVariants,
            'disabled_variants' => $disabledVariants,
            'extra_options' => $extraOptions,
        ];
        self::assertEquals($expected, $settings->toArray());
    }

    public function test_enable_variant(): void
    {
        $settings = new BuildSettings();
        $settings->enableVariant('curl');

        self::assertTrue($settings->isEnabledVariant('curl'));
    }

    public function test_enable_variants(): void
    {
        $variants = [
            'sqlite' => null,
            'curl' => 'yes',
            'dom' => null,
        ];
        $settings = new BuildSettings();
        $settings->enableVariants($variants);

        self::assertEquals($variants, $settings->getEnabledVariants());
    }

    public function test_disable_variants(): void
    {
        $variants = [
            'sqlite' => null, 'curl' => 'yes', 'dom' => null];
        $settings = new BuildSettings();
        $settings->disableVariants($variants);

        $expected = [
            'sqlite' => null,
            'curl' => null,
            'dom' => null,
        ];
        self::assertEquals($expected, $settings->getDisabledVariants());
    }

    public function test_is_enabled_variant(): void
    {
        $settings = new BuildSettings();
        $settings->enableVariant('sqlite');
        $settings->disableVariant('curl');

        self::assertTrue($settings->isEnabledVariant('sqlite'));
        self::assertFalse($settings->isEnabledVariant('curl'));
    }

    public function test_remove_variant(): void
    {
        $settings = new BuildSettings();
        $settings->enableVariant('sqlite');

        self::assertTrue($settings->isEnabledVariant('sqlite'));
        $settings->removeVariant('sqlite');
        self::assertFalse($settings->isEnabledVariant('sqlite'));
    }

    public function test_resolve_variants(): void
    {
        $settings = new BuildSettings();
        $settings->enableVariant('sqlite');
        $settings->disableVariant('sqlite');
        $settings->resolveVariants();

        self::assertEquals([], $settings->getEnabledVariants());
    }

    public function test_get_variants(): void
    {
        $settings = new BuildSettings();
        $settings->enableVariant('sqlite');
        $settings->enableVariant('curl');
        $settings->disableVariant('dom');

        self::assertEquals(['sqlite' => null, 'curl' => null], $settings->getEnabledVariants());
    }

    public function test_get_disabled_variants(): void
    {
        $settings = new BuildSettings();
        $settings->enableVariant('sqlite');
        $settings->enableVariant('curl');
        $settings->disableVariant('dom');

        self::assertEquals(['dom' => null], $settings->getDisabledVariants());
    }
}
