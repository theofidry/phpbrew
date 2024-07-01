<?php

namespace PhpBrew\Tests\Downloader;

use CLIFramework\Logger;
use GetOptionKit\OptionResult;
use PhpBrew\Config;
use PhpBrew\Downloader\CurlCommandDownloader;
use PhpBrew\Downloader\DownloadFactory;
use PhpBrew\Downloader\PhpCurlDownloader;
use PhpBrew\Downloader\PhpStreamDownloader;
use PhpBrew\Downloader\WgetCommandDownloader;
use PhpBrew\Testing\VCRAdapter;
use PHPUnit\Framework\TestCase;

/**
 * @large
 * @internal
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
    public function test_download_by_wget_command(): void
    {
        $this->assertDownloaderWorks(WgetCommandDownloader::class);
    }

    /**
     * @group noVCR
     */
    public function test_download_by_curl_command(): void
    {
        $this->assertDownloaderWorks(CurlCommandDownloader::class);
    }

    public function test_download_by_curl_extension(): void
    {
        $this->assertDownloaderWorks(PhpCurlDownloader::class);
    }

    public function test_download_by_file_function(): void
    {
        $this->assertDownloaderWorks(PhpStreamDownloader::class);
    }

    private function assertDownloaderWorks($downloader): void
    {
        $instance = DownloadFactory::getInstance($this->logger, new OptionResult(), $downloader);
        if ($instance->hasSupport(false)) {
            $actualFilePath = tempnam(Config::getTempFileDir(), '');
            $instance->download('http://httpbin.org/', $actualFilePath);
            self::assertFileExists($actualFilePath);
        } else {
            self::markTestSkipped();
        }
    }
}
