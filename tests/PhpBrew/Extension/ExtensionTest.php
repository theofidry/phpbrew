<?php

namespace PhpBrew\Tests\Extension;

use PhpBrew\Extension\Extension;
use PhpBrew\Extension\PeclExtension;
use PhpBrew\Extension\M4Extension;
use PhpBrew\Extension\ExtensionFactory;
use PhpBrew\Testing\VCRAdapter;
use PHPUnit\Framework\TestCase;

/**
 * ExtensionTest
 *
 * @large
 * @group extension
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
    public function testXdebug()
    {
        $ext = ExtensionFactory::lookup('xdebug', [getenv('PHPBREW_EXTENSION_DIR')]);
        $this->assertInstanceOf(Extension::class, $ext);
        $this->assertInstanceOf(PeclExtension::class, $ext);
        $this->assertEquals('xdebug', $ext->getName());
        $this->assertEquals('xdebug', $ext->getExtensionName());
        $this->assertEquals('xdebug.so', $ext->getSharedLibraryName());
        $this->assertTrue($ext->isZend());
    }

    public function testOpcache()
    {
        $ext = ExtensionFactory::lookup('opcache', [getenv('PHPBREW_EXTENSION_DIR')]);
        $this->assertInstanceOf(Extension::class, $ext);
        $this->assertInstanceOf(M4Extension::class, $ext);
        $this->assertEquals('opcache', $ext->getName());
        $this->assertEquals('opcache', $ext->getExtensionName());
        $this->assertEquals('opcache.so', $ext->getSharedLibraryName());
        $this->assertTrue($ext->isZend());
    }

    public function testOpenSSL()
    {
        $ext = ExtensionFactory::lookup('openssl', [getenv('PHPBREW_EXTENSION_DIR')]);
        $this->assertInstanceOf(Extension::class, $ext);
        $this->assertInstanceOf(M4Extension::class, $ext);
        $this->assertEquals('openssl', $ext->getName());
        $this->assertEquals('openssl', $ext->getExtensionName());
        $this->assertEquals('openssl.so', $ext->getSharedLibraryName());
        $this->assertFalse($ext->isZend());
    }

    public function testSoap()
    {
        $ext = ExtensionFactory::lookup('soap', [getenv('PHPBREW_EXTENSION_DIR')]);
        $this->assertInstanceOf(Extension::class, $ext);
        $this->assertInstanceOf(PeclExtension::class, $ext);
        $this->assertEquals('soap', $ext->getName());
        $this->assertEquals('soap', $ext->getExtensionName());
        $this->assertEquals('soap.so', $ext->getSharedLibraryName());
        $this->assertFalse($ext->isZend());
    }

    public function testSplTypes()
    {
        $ext = ExtensionFactory::lookup('SPL_Types', [getenv('PHPBREW_EXTENSION_DIR')]);
        $this->assertInstanceOf(Extension::class, $ext);
        $this->assertInstanceOf(PeclExtension::class, $ext);
        $this->assertEquals('SPL_Types', $ext->getName());
        $this->assertEquals('spl_types', $ext->getExtensionName());
        $this->assertEquals('spl_types.so', $ext->getSharedLibraryName());
        $this->assertFalse($ext->isZend());
    }

    public function testXhprof()
    {
        $ext = ExtensionFactory::lookup('xhprof', [getenv('PHPBREW_EXTENSION_DIR')]);
        $this->assertInstanceOf(Extension::class, $ext);
        $this->assertInstanceOf(PeclExtension::class, $ext);
        $this->assertEquals('xhprof', $ext->getName());
        $this->assertEquals('xhprof', $ext->getExtensionName());
        $this->assertEquals('xhprof.so', $ext->getSharedLibraryName());
        $this->assertFalse($ext->isZend());
    }

    public function extensionNameProvider()
    {
        $extNames = scandir(getenv('PHPBREW_EXTENSION_DIR'));
        $data = [];

        foreach ($extNames as $extName) {
            if ($extName == "." || $extName == "..") {
                continue;
            }
            $data[] = [$extName];
        }
        return $data;
    }


    /**
     * @dataProvider extensionNameProvider
     */
    public function testGenericExtensionMetaInformation($extName)
    {
        $ext = ExtensionFactory::lookup($extName, [getenv('PHPBREW_EXTENSION_DIR')]);
        $this->assertInstanceOf(Extension::class, $ext);
        $this->assertNotEmpty($ext->getName());
    }
}
