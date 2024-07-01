<?php

namespace PhpBrew\Tests\Downloader;

use PhpBrew\Downloader\WgetCommandDownloader;
use PhpBrew\Downloader\CurlCommandDownloader;
use PhpBrew\Downloader\PhpCurlDownloader;
use PhpBrew\Downloader\PhpStreamDownloader;
use CLIFramework\Logger;
use GetOptionKit\OptionResult;
use PhpBrew\Config;
use PhpBrew\Downloader\DownloadFactory;
use PhpBrew\Testing\VCRAdapter;
use PHPUnit\Framework\TestCase;

/**
 * @large
 */
class DownloaderTest extends TestCase
{
    public $logger;

    protected function setUp(): void
    {
        $this->logger = Logger::getInstance();
        $this->logger->setQuiet();

        VCRAdapter::enableVCR($this);
    }

    protected function tearDown(): void
    {
        VCRAdapter::disableVCR();
    }

    /**
     * @group noVCR
     */
    public function testDownloadByWgetCommand()
    {
        $this->assertDownloaderWorks(WgetCommandDownloader::class);
    }

    /**
     * @group noVCR
     */
    public function testDownloadByCurlCommand()
    {
        $this->assertDownloaderWorks(CurlCommandDownloader::class);
    }

    public function testDownloadByCurlExtension()
    {
        $this->assertDownloaderWorks(PhpCurlDownloader::class);
    }

    public function testDownloadByFileFunction()
    {
        $this->assertDownloaderWorks(PhpStreamDownloader::class);
    }

    private function assertDownloaderWorks($downloader)
    {
        $instance = DownloadFactory::getInstance($this->logger, new OptionResult(), $downloader);
        if ($instance->hasSupport(false)) {
            $actualFilePath = tempnam(Config::getTempFileDir(), '');
            $instance->download('http://httpbin.org/', $actualFilePath);
            $this->assertFileExists($actualFilePath);
        } else {
            $this->markTestSkipped();
        }
    }
}
