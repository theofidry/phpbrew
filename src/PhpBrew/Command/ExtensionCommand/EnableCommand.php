<?php

declare(strict_types=1);

namespace PhpBrew\Command\ExtensionCommand;

use PhpBrew\Config;
use PhpBrew\Extension\ExtensionManager;

class EnableCommand extends BaseCommand
{
    public function usage()
    {
        return 'phpbrew ext enable [extension name]';
    }

    public function brief()
    {
        return 'Enable PHP extension';
    }

    public function options($opts): void
    {
        $opts->add('s|sapi:=string', 'Enable extension for SAPI name.');
    }

    public function arguments($args): void
    {
        $args->add('extensions')
            ->suggestions(static function () {
                $extension = '.ini.disabled';

                return array_map(static function ($path) use ($extension) {
                    return basename($path, $extension);
                }, glob(Config::getCurrentPhpDir() . "/var/db/*{$extension}"));
            });
    }

    public function execute($extensionName): void
    {
        $sapi = null;
        if ($this->options->sapi) {
            $sapi = $this->options->sapi;
        }
        $manager = new ExtensionManager($this->logger);
        $manager->enable($extensionName, $sapi);
    }
}
