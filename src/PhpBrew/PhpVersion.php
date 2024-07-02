<?php

declare(strict_types=1);

namespace PhpBrew;

use LogicException;
use function preg_match;

final class PhpVersion
{
    private const PHP_STRING_VERSION_PATTERN = '/^(\d+)\.(\d+)/';

    /** @var positive-int */
    private $phpVersionId;

    public static function fromString(string $version): self {
        if (!preg_match(self::PHP_STRING_VERSION_PATTERN, $version, $matches)) {
            throw new LogicException("Invalid PHP version \"$version\"");
        }

        return self::fromComponents((int) $matches[1], (int) $matches[2]);
    }

    public static function fromComponents(int $major, int $minor): self {
        return new self($major * 10000 + $minor * 100);
    }

    /**
     * @param positive-int $phpVersionId e.g. `80308` for `8.3.8`.
     */
    public function __construct(int $phpVersionId)
    {
        $this->phpVersionId = $phpVersionId;
    }
}