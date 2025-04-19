<?php

declare(strict_types=1);

namespace PhpBrew\Variant;

use InvalidArgumentException;
use function array_key_exists;
use function assert;
use function get_class;
use function gettype;
use function is_object;
use function sprintf;

final class Variants
{
    /**
     * Variants indexed by their name.
     *
     * @var array<string, Variant>
     */
    private $indexedVariants = [];

    /**
     * @param Variant[] $variants
     */
    public function __construct(array $variants)
    {
        $namesByIndex = [];

        foreach ($variants as $index => $variant) {
            self::assertIsVariant($index, $variant);

            $name = $variant->getName();

            self::assertNoDuplicateNames(
                $name,
                $index,
                $variants,
                $namesByIndex
            );

            $this->indexedVariants[$name] = $variant;
            $namesByIndex[$name] = $index;
        }
    }

    /**
     * @param array-key $index
     * @param mixed $variant
     */
    private static function assertIsVariant($index, $variant): void
    {
        if (!($variant instanceof Variant)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Expected to have a collection of "%s". Got "%s" for the entry with the index "%s".',
                    Variant::class,
                    is_object($variant) ? get_class($variant) : gettype($variant),
                    $index
                )
            );
        }
    }

    /**
     * @param array-key $index
     * @param array<string, array-key> $namesByIndex
     */
    private static function assertNoDuplicateNames(
        string $name,
        $index,
        array $variants,
        $namesByIndex
    ): void
    {
        if (!array_key_exists($name, $variants)) {
            return;
        }

        $previousIndex = $namesByIndex[$name];

        throw new InvalidArgumentException(
            sprintf(
                'The variant "%s" with the index "%" already exists in the index "%s".',
                $name,
                $index,
                $previousIndex
            )
        );
    }
}