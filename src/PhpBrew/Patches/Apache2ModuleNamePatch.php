<?php

namespace PhpBrew\Patches;

use CLIFramework\Logger;
use PhpBrew\Buildable;
use PhpBrew\PatchKit\Patch;
use PhpBrew\PatchKit\RegExpPatchRule;

class Apache2ModuleNamePatch extends Patch
{
    private $targetPhpVersion;

    public function __construct($targetPhpVersion)
    {
        $this->targetPhpVersion = $targetPhpVersion;
    }

    public function desc()
    {
        return 'replace apache php module name with custom version name';
    }

    public function match(Buildable $build, Logger $logger)
    {
        return $build->isEnabledVariant('apxs2');
    }

    public function rules()
    {
        $rules = [];

        /*
        This is for replacing something like this:

        SAPI_SHARED=libs/libphp$PHP_MAJOR_VERSION.$SHLIB_DL_SUFFIX_NAME
        SAPI_STATIC=libs/libphp$PHP_MAJOR_VERSION.a
        SAPI_LIBTOOL=libphp$PHP_MAJOR_VERSION.la

        OVERALL_TARGET=libphp$PHP_MAJOR_VERSION.la

        OVERALL_TARGET=libs/libphp$PHP_MAJOR_VERSION.bundle

        SAPI_SHARED=libs/libphp5.so
        */
        if (version_compare($this->targetPhpVersion, '8.0') < 0) {
            $rules[] = RegExpPatchRule::files(['configure'])
                ->always()
                ->replaces(
                    '#libphp\$PHP_MAJOR_VERSION\.#',
                    'libphp$PHP_VERSION.'
                );


            $rules[] = RegExpPatchRule::files(['configure'])
                ->always()
                ->replaces(
                    '#libs/libphp[57].(so|la)#',
                    'libs/libphp\$PHP_VERSION.$1'
                );
        } else {
            $rules[] = RegExpPatchRule::files(['configure'])
                ->always()
                ->replaces(
                    '#libphp.(a|so|la|bundle)#',
                    'libphp$PHP_VERSION.$1'
                );

            $rules[] = RegExpPatchRule::files(['configure'])
                ->always()
                ->replaces(
                    '#libs/libphp.(a|so|la|bundle)#',
                    'libs/libphp\$PHP_VERSION.$1'
                );
            $rules[] = RegExpPatchRule::files(['configure'])
                ->always()
                ->replaces(
                    '#libs/libphp.\$SHLIB_DL_SUFFIX_NAME#',
                    'libs/libphp\$PHP_VERSION.$SHLIB_DL_SUFFIX_NAME'
                );
        }

        $makefile = 'Makefile.global';

        if (version_compare($this->targetPhpVersion, '8.0') >= 0) {
            $makefile = 'build/Makefile.global';
            $rules[] = RegExpPatchRule::files([$makefile])
                 ->always()
                 ->replaces(
                     '#libphp.(a|so|la|bundle)#',
                     'libphp$(PHP_VERSION).$1'
                 );

            $rules[] = RegExpPatchRule::files([$makefile])
                 ->always()
                 ->replaces(
                     '#libphp.\$\(SHLIB_DL_SUFFIX_NAME\)#',
                     'libphp$(PHP_VERSION).$(SHLIB_DL_SUFFIX_NAME)'
                 );
        }

        if (version_compare($this->targetPhpVersion, '7.4') >= 0) {
            $makefile = 'build/Makefile.global';
        }

        $rules[] = RegExpPatchRule::files([$makefile])
            ->always()
            ->replaces(
                '#libphp\$\(PHP_MAJOR_VERSION\)#',
                'libphp$(PHP_VERSION)'
            );

        return $rules;
    }
}
