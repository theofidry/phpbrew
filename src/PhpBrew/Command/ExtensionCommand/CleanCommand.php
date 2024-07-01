<?php

declare(strict_types=1);

namespace PhpBrew\Command\ExtensionCommand;

use PhpBrew\Config;
use PhpBrew\Extension\ExtensionFactory;
use PhpBrew\Extension\ExtensionManager;

class CleanCommand extends BaseCommand
{
    public function brief()
    {
        return 'Clean up the compiled objects in the extension source directory.';
    }

    public function options($opts): void
    {
        $opts->add('p|purge', 'Remove all the source files.');
    }

    public function arguments($args): void
    {
        $args->add('extensions')
            ->suggestions(static function () {
                $extdir = Config::getBuildDir() . '/' . Config::getCurrentPhpName() . '/ext';

                return array_filter(
                    scandir($extdir),
                    static function ($d) use ($extdir) {
                        return $d != '.' && $d != '..' && is_dir($extdir . DIRECTORY_SEPARATOR . $d);
                    }
                );
            });
    }

    public function execute($extensionName): void
    {
        if ($ext = ExtensionFactory::lookup($extensionName)) {
            $this->logger->info("Cleaning {$extensionName}...");
            $manager = new ExtensionManager($this->logger);

            if ($this->options->purge) {
                $manager->purgeExtension($ext);
            } else {
                $manager->cleanExtension($ext);
            }
            $this->logger->info('Done');
        }
    }
}
