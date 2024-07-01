<?php

namespace PhpBrew\Command\ExtensionCommand;

use PhpBrew\Config;
use PhpBrew\Extension\ExtensionManager;

class DisableCommand extends BaseCommand
{
    public function usage()
    {
        return 'phpbrew ext disable [extension name]';
    }

    public function brief()
    {
        return 'Disable PHP extension';
    }

    public function options($opts): void
    {
        $opts->add('s|sapi:=string', 'Disable extension for SAPI name.');
    }

    public function arguments($args): void
    {
        $args->add('extensions')
            ->suggestions(static function () {
                $extension = '.ini';

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
        $manager->disable($extensionName, $sapi);
    }
}
