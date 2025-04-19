<?php

declare(strict_types=1);

namespace PhpBrew\Variant;

use PhpBrew\ConfigureParameters;
use PhpBrew\PhpVersion;

final class VariantRegistry
{
    public static function createBuiltIn(): self
    {
        return new Variants([
            new NoValueVariant(
                '--disable-all',
            ),
            new Variant(
                'xml',
                'Add LIBXML support.',
                'https://www.php.net/manual/en/book.libxml.php',
                static function (bool $enabled, PhpVersion $build, ConfigureParameters $config): ConfigureParameters {
                    if (!$enabled) {
                        return $config;
                    }

                    if ($build->isLowerThan('7.4') < 0) {
                        $config = $config->withOption('--enable-libxml');

                        if ($prefix !== null) {
                            $parameters = $parameters->withOption('--with-libxml-dir', $prefix);
                        }
                    } else {
                        $parameters = $parameters->withOption('--with-libxml');

                        if ($prefix !== null) {
                            $parameters = $parameters->withPkgConfigPath($prefix . '/lib/pkgconfig');
                        }
                    }
                },
            ),
            Variant::withOptionWhenEnabled(
                'json',
                'Add JSON support.',
                'https://www.php.net/manual/en/json.installation.php',
                '--enable-json',
                null,
                PhpVersion::fromString('8.0')
            ),
        ]);
    }

    public function __construct(Variants $variants)
    {
        $this->variants = $variants;
    }

    public function getSupportedVariants()
    {

    }
}