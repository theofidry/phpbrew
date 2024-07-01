<?php

declare(strict_types=1);

namespace PhpBrew\Tests;

use PhpBrew\Build;
use PhpBrew\VariantBuilder;
use PHPUnit\Framework\TestCase;

/**
 * @small
 * @group macosIncompatible
 * @internal
 */
class VariantBuilderTest extends TestCase
{
    public static function variantOptionProvider(): iterable
    {
        return [
            'apxs2' => [
                ['apxs2'],
                ['--with-apxs2'],
            ],
            'bz2' => [
                ['bz2'],
                ['--with-bz2'],
            ],
            'curl' => [
                ['curl'],
                ['--with-curl'],
            ],
            'debug' => [
                ['debug'],
                ['--enable-debug'],
            ],
            'editline' => [
                ['editline'],
                ['--with-libedit'],
            ],
            'gd' => [
                ['gd'],
                [
                    '--with-gd',
                    '--with-png-dir',
                    '--with-jpeg-dir',
                ],
            ],
            'gettext' => [
                ['gettext'],
                ['--with-gettext'],
            ],
            'gmp' => [
                ['gmp'],
                ['--with-gmp'],
            ],
            'iconv' => [
                ['iconv'],
                ['--with-iconv'],
            ],
            'intl' => [
                ['intl'],
                ['--enable-intl'],
            ],
            'ipc' => [
                ['ipc'],
                [
                    '--enable-shmop',
                    '--enable-sysvshm',
                ],
            ],
            'mcrypt' => [
                ['mcrypt'],
                ['--with-mcrypt'],
            ],
            'mhash' => [
                ['mhash'],
                ['--with-mhash'],
            ],
            'mysql' => [
                ['mysql'],
                ['--with-mysqli'],
            ],
            'openssl' => [
                ['openssl'],
                ['--with-openssl'],
            ],
            'pdo-mysql' => [
                [
                    'mysql',
                    'pdo',
                ],
                ['--with-pdo-mysql'],
            ],
            'pdo-pgsql' => [
                [
                    'pgsql',
                    'pdo',
                ],
                ['--with-pdo-pgsql'],
            ],
            'pdo-sqlite' => [
                [
                    'sqlite',
                    'pdo',
                ],
                ['--with-pdo-sqlite'],
            ],
            'pgsql' => [
                ['pgsql'],
                ['--with-pgsql'],
            ],
            'readline' => [
                ['readline'],
                ['--with-readline'],
            ],
            'sqlite' => [
                ['sqlite'],
                ['--with-sqlite3'],
            ],
            'xml' => [
                ['xml'],
                [
                    '--enable-dom',
                    '--enable-libxml',
                    '--enable-simplexml',
                    '--with-libxml-dir',
                ],
            ],
            'zlib' => [
                ['zlib'],
                ['--with-zlib'],
            ],
            'snmp' => [
                ['snmp'],
                ['--with-snmp'],
            ],
        ];
    }

    /**
     * @dataProvider variantOptionProvider
     * @param mixed $expectedOptions
     */
    public function test_variant_option(array $variants, $expectedOptions): void
    {
        $build = new Build('5.5.0');
        foreach ($variants as $variant) {
            if (getenv('GITHUB_ACTIONS') && in_array($variant, ['apxs2', 'gd', 'editline'], true)) {
                self::markTestSkipped("GitHub actions doesn't support {$variant}}.");
            }

            $build->enableVariant($variant);
        }
        $build->resolveVariants();
        $variantBuilder = new VariantBuilder();
        $options = $variantBuilder->build($build)->getOptions();

        foreach ($expectedOptions as $expectedOption) {
            self::assertArrayHasKey($expectedOption, $options);
        }
    }

    public function test(): void
    {
        $variants = new VariantBuilder();
        $build = new Build('5.3.0');
        $build->enableVariant('debug');
        $build->enableVariant('sqlite');
        $build->enableVariant('xml');
        $build->enableVariant('apxs2', '/opt/local/apache2/apxs2');

        $build->disableVariant('sqlite');
        $build->disableVariant('mysql');
        $build->resolveVariants();
        $options = $variants->build($build)->getOptions();

        self::assertArrayHasKey('--enable-debug', $options);
        self::assertArrayHasKey('--enable-libxml', $options);
        self::assertArrayHasKey('--enable-simplexml', $options);

        self::assertArrayHasKey('--with-apxs2', $options);
        self::assertSame('/opt/local/apache2/apxs2', $options['--with-apxs2']);

        self::assertArrayHasKey('--without-sqlite3', $options);
        self::assertArrayHasKey('--without-mysql', $options);
        self::assertArrayHasKey('--without-mysqli', $options);
        self::assertArrayHasKey('--disable-all', $options);
    }

    public function test_everything(): void
    {
        $variants = new VariantBuilder();

        $build = new Build('5.6.0');
        $build->enableVariant('everything');
        $build->disableVariant('openssl');
        $build->resolveVariants();

        $options = $variants->build($build)->getOptions();

        self::assertArrayNotHasKey('--enable-all', $options);
        self::assertArrayNotHasKey('--with-apxs2', $options);
        self::assertArrayNotHasKey('--with-openssl', $options);
    }

    public function test_mysql_pdo_variant(): void
    {
        $variants = new VariantBuilder();

        $build = new Build('5.3.0');
        $build->enableVariant('pdo');
        $build->enableVariant('mysql');
        $build->enableVariant('sqlite');
        $build->resolveVariants();

        $options = $variants->build($build)->getOptions();
        self::assertArrayHasKey('--enable-pdo', $options);
        self::assertArrayHasKey('--with-mysql', $options);
        self::assertSame('mysqlnd', $options['--with-mysql']);
        self::assertArrayHasKey('--with-mysqli', $options);
        self::assertSame('mysqlnd', $options['--with-mysqli']);
        self::assertArrayHasKey('--with-pdo-mysql', $options);
        self::assertSame('mysqlnd', $options['--with-pdo-mysql']);
        self::assertArrayHasKey('--with-pdo-sqlite', $options);
    }

    public function test_all_variant(): void
    {
        $variants = new VariantBuilder();
        $build = new Build('5.3.0');
        $build->enableVariant('all');
        $build->disableVariant('mysql');
        $build->disableVariant('apxs2');
        $build->resolveVariants();

        $options = $variants->build($build)->getOptions();
        self::assertArrayHasKey('--enable-all', $options);
        self::assertArrayHasKey('--without-apxs2', $options);
        self::assertArrayHasKey('--without-mysql', $options);
    }

    /**
     * A test case for `neutral' virtual variant.
     */
    public function test_neutral_virtual_variant(): void
    {
        $variants = new VariantBuilder();
        $build = new Build('5.3.0');
        // $build->setVersion('5.3.0');
        $build->enableVariant('neutral');
        $build->resolveVariants();

        $options = $variants->build($build)->getOptions();
        // ignore `--with-libdir` because this option should be set depending on client environments
        unset($options['--with-libdir']);

        self::assertEquals([], $options);
    }

    /**
     * @param string $version
     * @param string $expected
     *
     * @dataProvider libXmlProvider
     */
    public function test_lib_xml($version, $expected): void
    {
        $build = new Build($version);
        $build->enableVariant('xml');

        $builder = new VariantBuilder();
        $options = $builder->build($build)->getOptions();

        self::assertArrayHasKey($expected, $options);
    }

    public static function libXmlProvider(): iterable
    {
        return [
            ['7.3.0', '--enable-libxml'],
            // see https://github.com/php/php-src/pull/4037
            ['7.4.0-dev', '--with-libxml'],
        ];
    }

    /**
     * @param string $version
     * @param string $expected
     *
     * @dataProvider zipProvider
     */
    public function test_zip($version, $expected): void
    {
        $build = new Build($version);
        $build->enableVariant('zip');

        $builder = new VariantBuilder();
        $options = $builder->build($build)->getOptions();

        self::assertArrayHasKey($expected, $options);
    }

    public static function zipProvider(): iterable
    {
        return [
            ['7.3.0', '--enable-zip'],
            // see https://github.com/php/php-src/pull/4072
            ['7.4.0-dev', '--with-zip'],
        ];
    }

    /**
     * @param string $version
     * @param string $expected
     *
     * @dataProvider ztsProvider
     */
    public function test_zts($version, $expected): void
    {
        $build = new Build($version);
        $build->enableVariant('zts');

        $builder = new VariantBuilder();
        $options = $builder->build($build)->getOptions();

        self::assertArrayHasKey($expected, $options);
    }

    public static function ztsProvider(): iterable
    {
        return [
            [
                '5.3.0',
                '--enable-maintainer-zts',
            ],
            [
                '5.4.0',
                '--enable-maintainer-zts',
            ],
            [
                '5.5.0',
                '--enable-maintainer-zts',
            ],
            [
                '5.6.0',
                '--enable-maintainer-zts',
            ],
            [
                '7.0.0',
                '--enable-maintainer-zts',
            ],
            [
                '7.1.0',
                '--enable-maintainer-zts',
            ],
            [
                '7.3.0',
                '--enable-maintainer-zts',
            ],
            [
                '7.4.0',
                '--enable-maintainer-zts',
            ],
            [
                '8.0.0',
                '--enable-zts',
            ],
        ];
    }

    /**
     * @param string $version
     * @param string $expected
     *
     * @dataProvider snmpProvider
     */
    public function test_snmp($version, $expected): void
    {
        $build = new Build($version);
        $build->enableVariant('snmp');

        $builder = new VariantBuilder();
        $options = $builder->build($build)->getOptions();

        self::assertArrayHasKey($expected, $options);
    }

    public static function snmpProvider(): iterable
    {
        return [
            [
                '5.6.0',
                '--with-snmp',
            ],
            [
                '7.0.0',
                '--with-snmp',
            ],
            [
                '7.1.0',
                '--with-snmp',
            ],
            [
                '7.3.0',
                '--with-snmp',
            ],
            [
                '7.4.0',
                '--with-snmp',
            ],
            [
                '8.0.0',
                '--with-snmp',
            ],
        ];
    }
}
