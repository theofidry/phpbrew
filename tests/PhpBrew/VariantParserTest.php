<?php

declare(strict_types=1);

namespace PhpBrew\Tests;

use PhpBrew\InvalidVariantSyntaxException;
use PhpBrew\VariantParser;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class VariantParserTest extends TestCase
{
    public function test(): void
    {
        self::assertEquals(
            [
                'enabled_variants' => [
                    'pdo' => null,
                    'sqlite' => null,
                    'debug' => null,
                    'apxs' => '/opt/local/apache2/bin/apxs',
                    'calendar' => null,
                ],
                'disabled_variants' => ['mysql' => null],
                'extra_options' => ['--with-icu-dir=/opt/local'],
            ],
            $this->parse(
                [
                    '+pdo',
                    '+sqlite',
                    '+debug',
                    '+apxs=/opt/local/apache2/bin/apxs',
                    '+calendar',
                    '-mysql',
                    '--',
                    '--with-icu-dir=/opt/local',
                ]
            )
        );
    }

    public function test_variant_all(): void
    {
        self::assertEquals(
            [
                'enabled_variants' => ['all' => null],
                'disabled_variants' => [
                    'apxs2' => null,
                    'mysql' => null,
                ],
                'extra_options' => [],
            ],
            $this->parse(
                [
                    '+all',
                    '-apxs2',
                    '-mysql',
                ]
            )
        );
    }

    /**
     * @dataProvider variantGroupOverloadProvider
     */
    public function test_variant_group_overload(array $args, array $expectedEnabledVariants): void
    {
        $info = $this->parse($args);
        self::assertEquals($expectedEnabledVariants, $info['enabled_variants']);
    }

    public static function variantGroupOverloadProvider(): iterable
    {
        return [
            'overrides default variant value' => [
                [
                    '+default',
                    '+openssl=/usr',
                ],
                [
                    'default' => null,
                    'openssl' => '/usr',
                ],
            ],
            'order must be irrelevant' => [
                [
                    '+openssl=/usr',
                    '+default',
                ],
                [
                    'openssl' => '/usr',
                    'default' => null,
                ],
            ],
            'negative variant' => [
                [
                    '+default',
                    '-openssl',
                ],
                ['default' => null],
            ],
            'negative variant precedence' => [
                [
                    '-openssl',
                    '+default',
                ],
                ['default' => null],
            ],
            'negative variant with an overridden value' => [
                [
                    '+default',
                    '-openssl=/usr',
                ],
                ['default' => null],
            ],
        ];
    }

    /**
     * @see https://github.com/phpbrew/phpbrew/issues/495
     */
    public function test_bug495(): void
    {
        self::assertEquals(
            [
                'enabled_variants' => ['gmp' => '/path/x86_64-linux-gnu'],
                'disabled_variants' => [
                    'openssl' => null,
                    'xdebug' => null,
                ],
                'extra_options' => [],
            ],
            $this->parse(
                [
                    '+gmp=/path/x86_64-linux-gnu',
                    '-openssl',
                    '-xdebug',
                ]
            )
        );
    }

    public function test_variant_user_value_contains_version(): void
    {
        self::assertEquals(
            [
                'enabled_variants' => [
                    'openssl' => '/usr/local/Cellar/openssl/1.0.2e',
                    'gettext' => '/usr/local/Cellar/gettext/0.19.7',
                ],
                'disabled_variants' => [],
                'extra_options' => [],
            ],
            $this->parse(
                [
                    '+openssl=/usr/local/Cellar/openssl/1.0.2e',
                    '+gettext=/usr/local/Cellar/gettext/0.19.7',
                ]
            )
        );
    }

    /**
     * @dataProvider revealCommandArgumentsProvider
     * @param mixed $expected
     */
    public function test_reveal_command_arguments(array $info, $expected): void
    {
        self::assertEquals($expected, VariantParser::revealCommandArguments($info));
    }

    public static function revealCommandArgumentsProvider(): iterable
    {
        return [
            [
                ['enabled_variants' => [
                    'mysql' => true,
                    'openssl' => '/usr',
                ],
                    'disabled_variants' => ['apxs2' => true],
                    'extra_options' => ['--with-icu-dir=/usr'],
                ],
                '+mysql +openssl=/usr -apxs2 -- --with-icu-dir=/usr',
            ],
        ];
    }

    /**
     * @dataProvider invalidSyntaxProvider
     * @requires PHPUnit 5.2
     * @param mixed $expectedMessage
     */
    public function test_invalid_syntax(array $args, $expectedMessage): void
    {
        $this->expectException(InvalidVariantSyntaxException::class);
        $this->expectExceptionMessage($expectedMessage);

        $this->parse($args);
    }

    public static function invalidSyntaxProvider(): iterable
    {
        return [
            'Empty argument' => [
                [''],
                'Variant cannot be empty',
            ],
            'Empty variant name' => [
                ['+'],
                'Variant name cannot be empty',
            ],
            'Empty variant name with value' => [
                ['-='],
                'Variant name cannot be empty',
            ],
            'Invalid operator' => [
                ['~'],
                'Variant must start with a + or -',
            ],
        ];
    }

    private function parse(array $args)
    {
        return VariantParser::parseCommandArguments($args);
    }
}
