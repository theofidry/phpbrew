<?php

namespace PhpBrew\Variant;

use Closure;
use PhpBrew\ConfigureParameters;
use PhpBrew\PhpVersion;

/**
 * @phpstan-type ConfigureVariant Closure(bool $enabled, PhpVersion $build, ConfigureParameters $config): ConfigureParameters
 */
final class Variant implements Variant
{
    /** @var string */
    private $name;
    /** @var string */
    private $description;
    /** @var string */
    private $configureOption;
    /** @var string */
    private $configureValue;

    public static function withOptionWhenEnabled(
        string $name,
        string $description,
        string $phpDocumentationLink,
        string $option,
        ?PhpVersion $minPhpVersion = null,
        ?PhpVersion $maxPhpVersion = null
    ): self
    {
        return new self(
            $name,
            $description,
            $phpDocumentationLink,
            static function (bool $enabled, PhpVersion $build, ConfigureParameters $config) use ($option): ConfigureParameters {
                return $enabled
                    ? $config->withOption($option)
                    : $config;
            },
            $minPhpVersion,
            $maxPhpVersion
        );
    }

    /**
     * @param string $name
     * @param string $description
     * @param string $phpDocumentationLink
     * @param ConfigureVariant $configure
     */
    public function __construct(
        string $name,
        string $description,
        string $phpDocumentationLink,
        Closure $configure,
        ?PhpVersion $minPhpVersion = null,
        ?PhpVersion $maxPhpVersion = null
    ) {
        $this->name = $name;
        $this->description = $description;
        $this->configureOption = $configure;
        $this->configureValue = $configureValue;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getConfigureOption(): string
    {
        return $this->configureOption;
    }

    public function getConfigureValue(): ?string
    {
        return $this->configureValue;
    }
}