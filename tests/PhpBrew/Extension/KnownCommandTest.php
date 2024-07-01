<?php

namespace PhpBrew\Tests\Extension;

use CLIFramework\Logger;
use GetOptionKit\OptionResult;
use PhpBrew\Extension\ExtensionDownloader;
use PhpBrew\Extension\Provider\BitbucketProvider;
use PhpBrew\Extension\Provider\GithubProvider;
use PhpBrew\Extension\Provider\PeclProvider;
use PhpBrew\Testing\CommandTestCase;

/**
 * @internal
 */
class KnownCommandTest extends CommandTestCase
{
    public $usesVCR = true;

    public function test_pecl_package(): void
    {
        $logger = new Logger();
        $logger->setQuiet();

        $provider = new PeclProvider($logger, new OptionResult());
        $provider->setPackageName('xdebug');

        $extensionDownloader = new ExtensionDownloader($logger, new OptionResult());
        $versionList = $extensionDownloader->knownReleases($provider);

        self::assertNotCount(0, $versionList);
    }

    public function test_github_package(): void
    {
        if (getenv('GITHUB_ACTIONS')) {
            self::markTestSkipped('Avoid bugging GitHub on Travis since the test is likely to fail because of a 403');
        }

        $logger = new Logger();
        $logger->setQuiet();

        $provider = new GithubProvider();
        $provider->setOwner('phalcon');
        $provider->setRepository('cphalcon');
        $provider->setPackageName('phalcon');
        if (getenv('github_token')) { // load token from travis-ci
            $provider->setAuth(getenv('github_token'));
        }

        $extensionDownloader = new ExtensionDownloader($logger, new OptionResult());
        $versionList = $extensionDownloader->knownReleases($provider);
        self::assertNotCount(0, $versionList);
    }

    public function test_bitbucket_package(): void
    {
        $logger = new Logger();
        $logger->setQuiet();

        $provider = new BitbucketProvider();
        $provider->setOwner('osmanov');
        $provider->setRepository('pecl-event');
        $provider->setPackageName('event');

        $extensionDownloader = new ExtensionDownloader($logger, new OptionResult());
        $versionList = $extensionDownloader->knownReleases($provider);

        self::assertNotCount(0, $versionList);
    }
}
