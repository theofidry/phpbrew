<?php

declare(strict_types=1);

namespace PhpBrew\Tests\Extension;

use PhpBrew\Extension\Extension;
use PhpBrew\Extension\ExtensionFactory;
use PhpBrew\Extension\M4Extension;
use PhpBrew\Extension\PeclExtension;
use PhpBrew\Testing\VCRAdapter;
use PHPUnit\Framework\TestCase;

/**
 * ExtensionTest.
 *
 * @large
 * @group extension
 * @internal
 */
class ExtensionTest extends TestCase
{
    protected function setUp(): void
    {
        VCRAdapter::enableVCR($this);
    }

    protected function tearDown(): void
    {
        VCRAdapter::disableVCR();
    }

    /**
     * We use getenv to get the path of extension directory because in data provider method
     * the path member is not setup yet.
     */
    public function test_xdebug(): void
    {
        $ext = ExtensionFactory::lookup('xdebug', [getenv('PHPBREW_EXTENSION_DIR')]);
        self::assertInstanceOf(Extension::class, $ext);
        self::assertInstanceOf(PeclExtension::class, $ext);
        self::assertEquals('xdebug', $ext->getName());
        self::assertEquals('xdebug', $ext->getExtensionName());
        self::assertEquals('xdebug.so', $ext->getSharedLibraryName());
        self::assertTrue($ext->isZend());
    }

    public function test_opcache(): void
    {
        $ext = ExtensionFactory::lookup('opcache', [getenv('PHPBREW_EXTENSION_DIR')]);
        self::assertInstanceOf(Extension::class, $ext);
        self::assertInstanceOf(M4Extension::class, $ext);
        self::assertEquals('opcache', $ext->getName());
        self::assertEquals('opcache', $ext->getExtensionName());
        self::assertEquals('opcache.so', $ext->getSharedLibraryName());
        self::assertTrue($ext->isZend());
    }

    public function test_open_ssl(): void
    {
        $ext = ExtensionFactory::lookup('openssl', [getenv('PHPBREW_EXTENSION_DIR')]);
        self::assertInstanceOf(Extension::class, $ext);
        self::assertInstanceOf(M4Extension::class, $ext);
        self::assertEquals('openssl', $ext->getName());
        self::assertEquals('openssl', $ext->getExtensionName());
        self::assertEquals('openssl.so', $ext->getSharedLibraryName());
        self::assertFalse($ext->isZend());
    }

    public function test_soap(): void
    {
        $ext = ExtensionFactory::lookup('soap', [getenv('PHPBREW_EXTENSION_DIR')]);
        self::assertInstanceOf(Extension::class, $ext);
        self::assertInstanceOf(PeclExtension::class, $ext);
        self::assertEquals('soap', $ext->getName());
        self::assertEquals('soap', $ext->getExtensionName());
        self::assertEquals('soap.so', $ext->getSharedLibraryName());
        self::assertFalse($ext->isZend());
    }

    public function test_spl_types(): void
    {
        $ext = ExtensionFactory::lookup('SPL_Types', [getenv('PHPBREW_EXTENSION_DIR')]);
        self::assertInstanceOf(Extension::class, $ext);
        self::assertInstanceOf(PeclExtension::class, $ext);
        self::assertEquals('SPL_Types', $ext->getName());
        self::assertEquals('spl_types', $ext->getExtensionName());
        self::assertEquals('spl_types.so', $ext->getSharedLibraryName());
        self::assertFalse($ext->isZend());
    }

    public function test_xhprof(): void
    {
        $ext = ExtensionFactory::lookup('xhprof', [getenv('PHPBREW_EXTENSION_DIR')]);
        self::assertInstanceOf(Extension::class, $ext);
        self::assertInstanceOf(PeclExtension::class, $ext);
        self::assertEquals('xhprof', $ext->getName());
        self::assertEquals('xhprof', $ext->getExtensionName());
        self::assertEquals('xhprof.so', $ext->getSharedLibraryName());
        self::assertFalse($ext->isZend());
    }

    public static function extensionNameProvider(): iterable
    {
        $extNames = scandir(getenv('PHPBREW_EXTENSION_DIR'));
        $data = [];

        foreach ($extNames as $extName) {
            if ($extName == '.' || $extName == '..') {
                continue;
            }
            $data[] = [$extName];
        }

        return $data;
    }

    /**
     * @dataProvider extensionNameProvider
     * @param mixed $extName
     */
    public function test_generic_extension_meta_information($extName): void
    {
        $ext = ExtensionFactory::lookup($extName, [getenv('PHPBREW_EXTENSION_DIR')]);
        self::assertInstanceOf(Extension::class, $ext);
        self::assertNotEmpty($ext->getName());
    }
}
