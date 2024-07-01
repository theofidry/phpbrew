<?php

namespace PhpBrew\Tests\Extension;

use CLIFramework\Logger;
use PhpBrew\Extension\ExtensionFactory;
use PhpBrew\Extension\ExtensionManager;
use PhpBrew\Testing\VCRAdapter;
use PHPUnit\Framework\TestCase;

/**
 * ExtensionManagerTest.
 *
 * @large
 * @group extension
 * @internal
 */
class ExtensionManagerTest extends TestCase
{
    private $manager;

    protected function setUp(): void
    {
        $logger = new Logger();
        $logger->setQuiet();
        $this->manager = new ExtensionManager($logger);

        VCRAdapter::enableVCR($this);
    }

    protected function tearDown(): void
    {
        VCRAdapter::disableVCR();
    }

    public function test_clean_extension(): void
    {
        $ext = ExtensionFactory::lookup('xdebug', [getenv('PHPBREW_EXTENSION_DIR')]);
        self::assertTrue($this->manager->cleanExtension($ext));
    }
}
