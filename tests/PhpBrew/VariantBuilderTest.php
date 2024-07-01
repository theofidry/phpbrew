<?php

namespace PhpBrew\Tests;

use PhpBrew\Build;
use PhpBrew\VariantBuilder;
use PHPUnit\Framework\TestCase;

/**
 * @small
 * @group macosIncompatible
 */
class VariantBuilderTest extends TestCase
{
    public function variantOptionProvider()
    {
        return [
            'apxs2'      => [
                ['apxs2'],
                ['--with-apxs2'],
            ],
            'bz2'        => [
                ['bz2'],
                ['--with-bz2'],
            ],
            'curl'       => [
                ['curl'],
                ['--with-curl'],
            ],
            'debug'      => [
                ['debug'],
                ['--enable-debug'],
            ],
            'editline'   => [
                ['editline'],
                ['--with-libedit'],
            ],
            'gd'         => [
                ['gd'],
                [
                    '--with-gd',
                    '--with-png-dir',
                    '--with-jpeg-dir',
                ],
            ],
            'gettext'    => [
                ['gettext'],
                ['--with-gettext'],
            ],
            'gmp'        => [
                ['gmp'],
                ['--with-gmp'],
            ],
            'iconv'      => [
                ['iconv'],
                ['--with-iconv'],
            ],
            'intl'       => [
                ['intl'],
                ['--enable-intl'],
            ],
            'ipc'        => [
                ['ipc'],
                [
                    '--enable-shmop',
                    '--enable-sysvshm',
                ],
            ],
            'mcrypt'     => [
                ['mcrypt'],
                ['--with-mcrypt'],
            ],
            'mhash'      => [
                ['mhash'],
                ['--with-mhash'],
            ],
            'mysql'      => [
                ['mysql'],
                ['--with-mysqli'],
            ],
            'openssl'    => [
                ['openssl'],
                ['--with-openssl'],
            ],
            'pdo-mysql'  => [
                [
                    'mysql',
                    'pdo',
                ],
                ['--with-pdo-mysql'],
            ],
            'pdo-pgsql'  => [
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
            'pgsql'      => [
                ['pgsql'],
                ['--with-pgsql'],
            ],
            'readline'   => [
                ['readline'],
                ['--with-readline'],
            ],
            'sqlite'     => [
                ['sqlite'],
                ['--with-sqlite3'],
            ],
            'xml'        => [
                ['xml'],
                [
                    '--enable-dom',
                    '--enable-libxml',
                    '--enable-simplexml',
                    '--with-libxml-dir',
                ],
            ],
            'zlib'       => [
                ['zlib'],
                ['--with-zlib'],
            ],
            'snmp'       => [
                ['snmp'],
                ['--with-snmp'],
            ],
        ];
    }

    /**
     * @dataProvider variantOptionProvider
     */
    public function testVariantOption(array $variants, $expectedOptions)
    {
        $build = new Build('5.5.0');
        foreach ($variants as $variant) {
            if (getenv('GITHUB_ACTIONS') && in_array($variant, ["apxs2", "gd", "editline"])) {
                $this->markTestSkipped("GitHub actions doesn't support $variant}.");
            }

            $build->enableVariant($variant);
        }
        $build->resolveVariants();
        $variantBuilder = new VariantBuilder();
        $options = $variantBuilder->build($build)->getOptions();

        foreach ($expectedOptions as $expectedOption) {
            $this->assertArrayHasKey($expectedOption, $options);
        }
    }

    public function test()
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

        $this->assertArrayHasKey('--enable-debug', $options);
        $this->assertArrayHasKey('--enable-libxml', $options);
        $this->assertArrayHasKey('--enable-simplexml', $options);

        $this->assertArrayHasKey('--with-apxs2', $options);
        $this->assertSame('/opt/local/apache2/apxs2', $options['--with-apxs2']);

        $this->assertArrayHasKey('--without-sqlite3', $options);
        $this->assertArrayHasKey('--without-mysql', $options);
        $this->assertArrayHasKey('--without-mysqli', $options);
        $this->assertArrayHasKey('--disable-all', $options);
    }

    public function testEverything()
    {
        $variants = new VariantBuilder();

        $build = new Build('5.6.0');
        $build->enableVariant('everything');
        $build->disableVariant('openssl');
        $build->resolveVariants();

        $options = $variants->build($build)->getOptions();

        $this->assertArrayNotHasKey('--enable-all', $options);
        $this->assertArrayNotHasKey('--with-apxs2', $options);
        $this->assertArrayNotHasKey('--with-openssl', $options);
    }

    public function testMysqlPdoVariant()
    {
        $variants = new VariantBuilder();

        $build = new Build('5.3.0');
        $build->enableVariant('pdo');
        $build->enableVariant('mysql');
        $build->enableVariant('sqlite');
        $build->resolveVariants();

        $options = $variants->build($build)->getOptions();
        $this->assertArrayHasKey('--enable-pdo', $options);
        $this->assertArrayHasKey('--with-mysql', $options);
        $this->assertSame('mysqlnd', $options['--with-mysql']);
        $this->assertArrayHasKey('--with-mysqli', $options);
        $this->assertSame('mysqlnd', $options['--with-mysqli']);
        $this->assertArrayHasKey('--with-pdo-mysql', $options);
        $this->assertSame('mysqlnd', $options['--with-pdo-mysql']);
        $this->assertArrayHasKey('--with-pdo-sqlite', $options);
    }

    public function testAllVariant()
    {
        $variants = new VariantBuilder();
        $build = new Build('5.3.0');
        $build->enableVariant('all');
        $build->disableVariant('mysql');
        $build->disableVariant('apxs2');
        $build->resolveVariants();

        $options = $variants->build($build)->getOptions();
        $this->assertArrayHasKey('--enable-all', $options);
        $this->assertArrayHasKey('--without-apxs2', $options);
        $this->assertArrayHasKey('--without-mysql', $options);
    }

    /**
     * A test case for `neutral' virtual variant.
     */
    public function testNeutralVirtualVariant()
    {
        $variants = new VariantBuilder();
        $build = new Build('5.3.0');
        // $build->setVersion('5.3.0');
        $build->enableVariant('neutral');
        $build->resolveVariants();

        $options = $variants->build($build)->getOptions();
        // ignore `--with-libdir` because this option should be set depending on client environments
        unset($options['--with-libdir']);

        $this->assertEquals([], $options);
    }

    /**
     * @param string $version
     * @param string $expected
     *
     * @dataProvider libXmlProvider
     */
    public function testLibXml($version, $expected)
    {
        $build = new Build($version);
        $build->enableVariant('xml');

        $builder = new VariantBuilder();
        $options = $builder->build($build)->getOptions();

        $this->assertArrayHasKey($expected, $options);
    }

    public static function libXmlProvider()
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
    public function testZip($version, $expected)
    {
        $build = new Build($version);
        $build->enableVariant('zip');

        $builder = new VariantBuilder();
        $options = $builder->build($build)->getOptions();

        $this->assertArrayHasKey($expected, $options);
    }

    public static function zipProvider()
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
    public function testZts($version, $expected)
    {
        $build = new Build($version);
        $build->enableVariant('zts');

        $builder = new VariantBuilder();
        $options = $builder->build($build)->getOptions();

        $this->assertArrayHasKey($expected, $options);
    }

    public static function ztsProvider()
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
    public function testSnmp($version, $expected)
    {
        $build = new Build($version);
        $build->enableVariant('snmp');

        $builder = new VariantBuilder();
        $options = $builder->build($build)->getOptions();

        $this->assertArrayHasKey($expected, $options);
    }

    public static function snmpProvider()
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
