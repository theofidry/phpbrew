<?php

namespace PhpBrew\Extension\Provider;

use Exception;

class GithubProvider implements Provider
{
    public $auth;
    public $site = 'github.com';
    public $owner;
    public $repository;
    public $packageName;
    public $defaultVersion = 'master';

    public static function getName()
    {
        return 'github';
    }

    /**
     * By default we install extension from master branch.
     * @param mixed $version
     */
    public function buildPackageDownloadUrl($version = 'master')
    {
        if (($this->getOwner() == null) || ($this->getRepository() == null)) {
            throw new Exception('Username or Repository invalid.');
        }

        return sprintf(
            'https://%s/%s/%s/archive/%s.tar.gz',
            $this->site,
            $this->getOwner(),
            $this->getRepository(),
            $version
        );
    }

    /**
     * @return string
     */
    public function getAuth()
    {
        return $this->auth;
    }

    /**
     * @param string $auth
     */
    public function setAuth($auth): void
    {
        $this->auth = $auth;
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

        return $info['repository'] == 'github';
    }

    public function isBundled($name)
    {
        return false;
    }

    public function buildKnownReleasesUrl()
    {
        return sprintf(
            'https://%sapi.github.com/repos/%s/%s/tags',
            $this->auth ? $this->auth . '@' : '',
            $this->getOwner(),
            $this->getRepository()
        );
    }

    public function parseKnownReleasesResponse($content)
    {
        $info = json_decode($content, true);

        return array_map(static function ($version) {
            return $version['name'];
        }, $info);
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
        return sprintf('%s-%s.tar.gz', $this->getRepository(), $version);
    }

    public function extractPackageCommands($currentPhpExtensionDirectory, $targetFilePath)
    {
        return ["tar -C {$currentPhpExtensionDirectory} -xzf {$targetFilePath}"];
    }

    public function postExtractPackageCommands($currentPhpExtensionDirectory, $targetFilePath)
    {
        $targetPkgDir = $currentPhpExtensionDirectory . DIRECTORY_SEPARATOR . $this->getPackageName();
        $extractDir = $currentPhpExtensionDirectory . DIRECTORY_SEPARATOR . $this->getRepository() . '-*';

        return [
            "rm -rf {$targetPkgDir}",
            "mv {$extractDir} {$targetPkgDir}",
        ];
    }
}
