<?php

namespace PhpBrew\Tests;

use PhpBrew\InvalidVariantSyntaxException;
use PhpBrew\VariantParser;
use PHPUnit\Framework\TestCase;

class VariantParserTest extends TestCase
{
    public function test()
    {
        $this->assertEquals(['enabled_variants' => ['pdo' => null, 'sqlite' => null, 'debug' => null, 'apxs' => '/opt/local/apache2/bin/apxs', 'calendar' => null], 'disabled_variants' => ['mysql' => null], 'extra_options' => ['--with-icu-dir=/opt/local']], $this->parse(['+pdo', '+sqlite', '+debug', '+apxs=/opt/local/apache2/bin/apxs', '+calendar', '-mysql', '--', '--with-icu-dir=/opt/local']));
    }

    public function testVariantAll()
    {
        $this->assertEquals(['enabled_variants' => ['all' => null], 'disabled_variants' => ['apxs2' => null, 'mysql' => null], 'extra_options' => []], $this->parse(['+all', '-apxs2', '-mysql']));
    }

    /**
     * @dataProvider variantGroupOverloadProvider
     */
    public function testVariantGroupOverload(array $args, array $expectedEnabledVariants)
    {
        $info = $this->parse($args);
        $this->assertEquals($expectedEnabledVariants, $info['enabled_variants']);
    }

    public static function variantGroupOverloadProvider()
    {
        return ['overrides default variant value' => [['+default', '+openssl=/usr'], ['default' => null, 'openssl' => '/usr']], 'order must be irrelevant' => [['+openssl=/usr', '+default'], ['openssl' => '/usr', 'default' => null]], 'negative variant' => [['+default', '-openssl'], ['default' => null]], 'negative variant precedence' => [['-openssl', '+default'], ['default' => null]], 'negative variant with an overridden value' => [['+default', '-openssl=/usr'], ['default' => null]]];
    }

    /**
     * @link https://github.com/phpbrew/phpbrew/issues/495
     */
    public function testBug495()
    {
        $this->assertEquals(['enabled_variants' => ['gmp' => '/path/x86_64-linux-gnu'], 'disabled_variants' => ['openssl' => null, 'xdebug' => null], 'extra_options' => []], $this->parse(['+gmp=/path/x86_64-linux-gnu', '-openssl', '-xdebug']));
    }

    public function testVariantUserValueContainsVersion()
    {
        $this->assertEquals(['enabled_variants' => ['openssl' => '/usr/local/Cellar/openssl/1.0.2e', 'gettext' => '/usr/local/Cellar/gettext/0.19.7'], 'disabled_variants' => [], 'extra_options' => []], $this->parse(['+openssl=/usr/local/Cellar/openssl/1.0.2e', '+gettext=/usr/local/Cellar/gettext/0.19.7']));
    }

    /**
     * @dataProvider revealCommandArgumentsProvider
     */
    public function testRevealCommandArguments(array $info, $expected)
    {
        $this->assertEquals($expected, VariantParser::revealCommandArguments($info));
    }

    public static function revealCommandArgumentsProvider()
    {
        return [[['enabled_variants' => ['mysql' => true, 'openssl' => '/usr'], 'disabled_variants' => ['apxs2' => true], 'extra_options' => ['--with-icu-dir=/usr']], '+mysql +openssl=/usr -apxs2 -- --with-icu-dir=/usr']];
    }

    /**
     * @dataProvider invalidSyntaxProvider
     * @requires PHPUnit 5.2
     */
    public function testInvalidSyntax(array $args, $expectedMessage)
    {
        $this->expectException(InvalidVariantSyntaxException::class);
        $this->expectExceptionMessage($expectedMessage);

        $this->parse($args);
    }

    public static function invalidSyntaxProvider()
    {
        return ['Empty argument' => [[''], 'Variant cannot be empty'], 'Empty variant name' => [['+'], 'Variant name cannot be empty'], 'Empty variant name with value' => [['-='], 'Variant name cannot be empty'], 'Invalid operator' => [['~'], 'Variant must start with a + or -']];
    }

    private function parse(array $args)
    {
        return VariantParser::parseCommandArguments($args);
    }
}
