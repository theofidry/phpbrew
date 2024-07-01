<?php

declare(strict_types=1);

namespace PhpBrew\Extension;

use PhpBrew\Buildable;
use PhpBrew\Config;

class Extension implements Buildable
{
    /**
     * @var string The extension package name
     *
     * The package name does not equal to the extension name.
     * for example, "APCu" provides "apcu" instead of "APCu"
     */
    protected $name;

    protected $extensionName;

    protected $version;

    /**
     * @var string config.m4 filename
     */
    protected $configM4File = 'config.m4';

    /**
     * The extension so name.
     */
    protected $sharedLibraryName;

    protected $sourceDirectory;

    protected $isZend = false;

    /**
     * @var ConfigureOption[]
     *
     * Contains [($name, $desc), .... ] pairs
     */
    protected $configureOptions = [];

    protected static $nameMap = ['libsodium' => 'sodium'];

    public function __construct($name)
    {
        $this->name = $name;
        $this->extensionName = strtolower($name);
    }

    public function setName($name)
    {
        return $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setVersion($version): void
    {
        $this->version = $version;
    }

    public function getVersion()
    {
        return $this->version;
    }

    public function setZend($zendExtension = true): void
    {
        $this->isZend = $zendExtension;
    }

    public function isZend()
    {
        return $this->isZend;
    }

    public function setSharedLibraryName($n): void
    {
        $this->sharedLibraryName = $n;
    }

    public function getSharedLibraryName()
    {
        if ($this->sharedLibraryName) {
            return $this->sharedLibraryName;
        }

        $name = strtolower($this->extensionName);
        if (isset(self::$nameMap[$name])) {
            $name = self::$nameMap[$name];
        }

        return $name . '.so'; // for windows it might be a DLL.
    }

    public function setExtensionName($name): void
    {
        $this->extensionName = $name;
    }

    public function getExtensionName()
    {
        return $this->extensionName;
    }

    public function setSourceDirectory($dir): void
    {
        $this->sourceDirectory = $dir;

        if ($configM4File = $this->findConfigM4File($dir)) {
            $this->configM4File = $configM4File;
        }
    }

    public function getConfigM4File()
    {
        return $this->configM4File;
    }

    public function getConfigM4Path()
    {
        return $this->sourceDirectory . DIRECTORY_SEPARATOR . $this->configM4File;
    }

    public function findConfigM4File($dir)
    {
        $configM4Path = $dir . DIRECTORY_SEPARATOR . 'config.m4';
        if (file_exists($configM4Path)) {
            return 'config.m4';
        }

        for ($i = 0; $i < 10; ++$i) {
            $filename = "config{$i}.m4";
            $configM4Path = $dir . DIRECTORY_SEPARATOR . $filename;
            if (file_exists($configM4Path)) {
                return $filename;
            }
        }
    }

    public function isBuildable()
    {
        return file_exists($this->sourceDirectory . DIRECTORY_SEPARATOR . 'Makefile');
    }

    public function getSourceDirectory()
    {
        return $this->sourceDirectory;
    }

    public function getBuildLogPath()
    {
        return $this->sourceDirectory . DIRECTORY_SEPARATOR . 'build.log';
    }

    public function getSharedLibraryPath()
    {
        return ini_get('extension_dir') . DIRECTORY_SEPARATOR . $this->getSharedLibraryName();
    }

    public function getConfigFilePath($sapi = null)
    {
        $sapiPath = '';
        if ($sapi) {
            $sapiPath = '/' . $sapi;
        }

        return Config::getCurrentPhpConfigScanPath() . $sapiPath . '/' . $this->getName() . '.ini';
    }

    /**
     * Checks if current extension is loaded.
     *
     * @return bool
     */
    public function isLoaded()
    {
        return extension_loaded($this->extensionName);
    }

    public function addConfigureOption(ConfigureOption $opt): void
    {
        $this->configureOptions[] = $opt;
    }

    public function getConfigureOptions()
    {
        return $this->configureOptions;
    }
}
