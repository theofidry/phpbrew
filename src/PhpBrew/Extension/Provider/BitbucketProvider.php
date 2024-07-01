<?php

namespace PhpBrew\Extension\Provider;

use Exception;

class BitbucketProvider implements Provider
{
    public $site = 'bitbucket.org';
    public $owner;
    public $repository;
    public $packageName;
    public $defaultVersion = 'master';

    public static function getName()
    {
        return 'bitbucket';
    }

    public function buildPackageDownloadUrl($version = 'stable')
    {
        if (($this->getOwner() == null) || ($this->getRepository() == null)) {
            throw new Exception('Username or Repository invalid.');
        }

        return sprintf(
            'https://%s/%s/%s/get/%s.tar.gz',
            $this->site,
            $this->getOwner(),
            $this->getRepository(),
            $version
        );
    }

    public function getOwner()
    {
        return $this->owner;
    }

    public function setOwner($owner): void
    {
        $this->owner = $owner;
    }

    public function getRepository()
    {
        return $this->repository;
    }

    public function setRepository($repository): void
    {
        $this->repository = $repository;
    }

    public function getPackageName()
    {
        return $this->packageName;
    }

    public function setPackageName($packageName): void
    {
        $this->packageName = $packageName;
    }

    public function exists($dsl, $packageName = null)
    {
        $dslparser = new RepositoryDslParser();
        $info = $dslparser->parse($dsl);

        $this->setOwner($info['owner']);
        $this->setRepository($info['package']);
        $this->setPackageName($packageName ?: $info['package']);

        return $info['repository'] == 'bitbucket';
    }

    public function isBundled($name)
    {
        return false;
    }

    public function buildKnownReleasesUrl()
    {
        return sprintf(
            'https://bitbucket.org/api/2.0/repositories/%s/%s/refs/tags',
            rawurlencode($this->getOwner()),
            rawurlencode($this->getRepository())
        );
    }

    public function parseKnownReleasesResponse($content)
    {
        $info = json_decode($content, true);

        return array_keys($info);
    }

    public function getDefaultVersion()
    {
        return $this->defaultVersion;
    }

    public function setDefaultVersion($version): void
    {
        $this->defaultVersion = $version;
    }

    public function shouldLookupRecursive()
    {
        return true;
    }

    public function resolveDownloadFileName($version)
    {
        return sprintf('%s-%s-%s.tar.gz', $this->getOwner(), $this->getRepository(), $version);
    }

    public function extractPackageCommands($currentPhpExtensionDirectory, $targetFilePath)
    {
        return ["tar -C {$currentPhpExtensionDirectory} -xzf {$targetFilePath}"];
    }

    public function postExtractPackageCommands($currentPhpExtensionDirectory, $targetFilePath)
    {
        $targetPkgDir = $currentPhpExtensionDirectory . DIRECTORY_SEPARATOR . $this->getPackageName();
        $extractDir = $currentPhpExtensionDirectory
            . DIRECTORY_SEPARATOR
            . $this->getOwner()
            . '-'
            . $this->getRepository()
            . '-*';

        return [
            "rm -rf {$targetPkgDir}",
            "mv {$extractDir} {$targetPkgDir}",
        ];
    }
}
