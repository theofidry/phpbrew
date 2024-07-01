<?php

declare(strict_types=1);

namespace PhpBrew\Tests;

use Exception;
use PhpBrew\Config;
use PHPUnit\Framework\TestCase;

/**
 * You should use predefined $PHPBREW_HOME and $PHPBREW_ROOT (defined
 * in phpunit.xml), because they are used to create directories in
 * PhpBrew\Config class. When you want to set $PHPBREW_ROOT, $PHPBREW_HOME
 * or $HOME, you should get its value by calling `getenv' function and set
 * the value to the corresponding environment variable.
 * @small
 * @internal
 */
class ConfigTest extends TestCase
{
    /**
     * @expectedException \Exception
     */
    public function test_get_phpbrew_home_when_home_is_not_defined(): void
    {
        $env = [
            'PHPBREW_HOME' => null,
            'PHPBREW_ROOT' => null,
            'HOME' => null,
        ];
        $this->withEnv($env, static function (): void {
            Config::getHome();
        });
    }

    public function test_get_phpbrew_home_when_home_is_defined(): void
    {
        $env = [
            'HOME' => getenv('PHPBREW_ROOT'),
            'PHPBREW_HOME' => null,
        ];
        $this->withEnv($env, static function ($self): void {
            $self->assertStringEndsWith('.phpbrew/.phpbrew', Config::getHome());
        });
    }

    public function test_get_phpbrew_home_when_php_brew_home_is_defined(): void
    {
        $this->withEnv([], static function ($self): void {
            $self->assertStringEndsWith('.phpbrew', Config::getHome());
        });
    }

    public function test_get_phpbrew_root_when_php_brew_root_is_defined(): void
    {
        $this->withEnv([], static function ($self): void {
            $self->assertStringEndsWith('.phpbrew', Config::getRoot());
        });
    }

    public function test_get_phpbrew_root_when_home_is_defined(): void
    {
        $env = [
            'HOME' => getenv('PHPBREW_ROOT'),
            'PHPBREW_ROOT' => null,
        ];
        $this->withEnv($env, static function ($self): void {
            $self->assertStringEndsWith('.phpbrew/.phpbrew', Config::getRoot());
        });
    }

    public function test_get_build_dir(): void
    {
        $this->withEnv([], static function ($self): void {
            $self->assertStringEndsWith('.phpbrew/build', Config::getBuildDir());
        });
    }

    public function test_get_dist_file_dir(): void
    {
        $this->withEnv([], static function ($self): void {
            $self->assertStringEndsWith('.phpbrew/distfiles', Config::getDistFileDir());
        });
    }

    public function test_get_temp_file_dir(): void
    {
        $this->withEnv([], static function ($self): void {
            $self->assertStringEndsWith('.phpbrew/tmp', Config::getTempFileDir());
        });
    }

    public function test_get_current_php_name(): void
    {
        $env = ['PHPBREW_PHP' => '5.6.3'];
        $this->withEnv($env, static function ($self): void {
            $self->assertStringEndsWith('5.6.3', Config::getCurrentPhpName());
        });
    }

    public function test_get_current_build_dir(): void
    {
        $env = ['PHPBREW_PHP' => '5.6.3'];
        $this->withEnv($env, static function ($self): void {
            $self->assertStringEndsWith('.phpbrew/build/5.6.3', Config::getCurrentBuildDir());
        });
    }

    public function test_get_php_release_list_path(): void
    {
        $this->withEnv([], static function ($self): void {
            $self->assertStringEndsWith('.phpbrew/php-releases.json', Config::getPHPReleaseListPath());
        });
    }

    public function test_get_install_prefix(): void
    {
        $this->withEnv([], static function ($self): void {
            $self->assertStringEndsWith('.phpbrew/php', Config::getInstallPrefix());
        });
    }

    public function test_get_version_install_prefix(): void
    {
        $this->withEnv([], static function ($self): void {
            $self->assertStringEndsWith('.phpbrew/php/5.5.1', Config::getVersionInstallPrefix('5.5.1'));
        });
    }

    public function test_get_version_etc_path(): void
    {
        $this->withEnv([], static function ($self): void {
            $self->assertStringEndsWith('.phpbrew/php/5.5.1/etc', Config::getVersionEtcPath('5.5.1'));
        });
    }

    public function test_get_version_bin_path(): void
    {
        $this->withEnv([], static function ($self): void {
            $self->assertStringEndsWith('.phpbrew/php/5.5.1/bin', Config::getVersionBinPath('5.5.1'));
        });
    }

    public function test_get_current_php_config_bin(): void
    {
        $env = ['PHPBREW_PHP' => '5.5.1'];
        $this->withEnv($env, static function ($self): void {
            $self->assertStringEndsWith('.phpbrew/php/5.5.1/bin/php-config', Config::getCurrentPhpConfigBin());
        });
    }

    public function test_get_current_phpize_bin(): void
    {
        $env = ['PHPBREW_PHP' => '5.5.1'];
        $this->withEnv($env, static function ($self): void {
            $self->assertStringEndsWith('.phpbrew/php/5.5.1/bin/phpize', Config::getCurrentPhpizeBin());
        });
    }

    public function test_get_current_php_config_scan_path(): void
    {
        $env = ['PHPBREW_PHP' => '5.5.1'];
        $this->withEnv($env, static function ($self): void {
            $self->assertStringEndsWith('.phpbrew/php/5.5.1/var/db', Config::getCurrentPhpConfigScanPath());
        });
    }

    public function test_get_current_php_dir(): void
    {
        $env = ['PHPBREW_PHP' => '5.5.1'];
        $this->withEnv($env, static function ($self): void {
            $self->assertStringEndsWith('.phpbrew/php/5.5.1', Config::getCurrentPhpDir());
        });
    }

    public function test_get_lookup_prefix(): void
    {
        $env = ['PHPBREW_LOOKUP_PREFIX' => getenv('PHPBREW_ROOT')];
        $this->withEnv($env, static function ($self): void {
            $self->assertStringEndsWith('.phpbrew', Config::getLookupPrefix());
        });
    }

    public function test_get_current_php_bin(): void
    {
        $env = ['PHPBREW_PATH' => getenv('PHPBREW_ROOT')];
        $this->withEnv($env, static function ($self): void {
            $self->assertStringEndsWith('.phpbrew', Config::getCurrentPhpBin());
        });
    }

    public function test_get_config_param(): void
    {
        $env = [
            // I guess this causes the failure here: https://travis-ci.org/phpbrew/phpbrew/jobs/95057923
            // 'PHPBREW_ROOT' => __DIR__ . '/../fixtures',
            'PHPBREW_ROOT' => 'tests/fixtures',
        ];
        $this->withEnv($env, static function ($self): void {
            $config = Config::getConfig();
            $self->assertSame(['key1' => 'value1', 'key2' => 'value2'], $config);
            $self->assertEquals('value1', Config::getConfigParam('key1'));
            $self->assertEquals('value2', Config::getConfigParam('key2'));
        });
    }

    /**
     * PHPBREW_HOME and PHPBREW_ROOT are automatically defined if
     * the function which invokes this method doesn't set them explicitly.
     * Set PHPBREW_HOME and PHPBREW_ROOT to null when you want to unset them.
     * @param mixed $newEnv
     * @param mixed $callback
     */
    public function withEnv($newEnv, $callback): void
    {
        // reset environment variables
        $oldEnv = $this->resetEnv(
            $newEnv + [
                'HOME' => null,
                'PHPBREW_HOME' => getenv('PHPBREW_HOME'),
                'PHPBREW_PATH' => null,
                'PHPBREW_PHP' => null,
                'PHPBREW_ROOT' => getenv('PHPBREW_ROOT'),
                'PHPBREW_LOOKUP_PREFIX' => null,
            ]
        );

        try {
            $callback($this);
            $this->resetEnv($oldEnv);
        } catch (Exception $e) {
            $this->resetEnv($oldEnv);

            throw $e;
        }
    }

    public function resetEnv($env)
    {
        $oldEnv = [];
        foreach ($env as $key => $value) {
            $oldEnv[$key] = getenv($key);
            $this->putEnv($key, $value);
        }

        return $oldEnv;
    }

    public function putEnv($key, $value): void
    {
        $setting = $key;

        if ($value !== null) {
            $setting .= '=' . $value;
        }

        self::assertTrue(putenv($setting));
    }
}
