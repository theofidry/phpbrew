<?php

declare(strict_types=1);

namespace PhpBrew\Tests\Extension\Provider;

use PhpBrew\Extension\Provider\RepositoryDslParser;
use PHPUnit\Framework\TestCase;

/**
 * ExtensionDslParserTest.
 *
 * @small
 * @group extension
 * @internal
 */
class RepositoryDslParserTest extends TestCase
{
    protected $parser;

    protected function setUp(): void
    {
        $this->parser = new RepositoryDslParser();
    }

    public static function dslProvider(): iterable
    {
        return [
            // pecl
            ['xdebug', 'pecl', null, 'xdebug'],
            // standard pecl package name
            ['APCu', 'pecl', null, 'APCu'],
            // pecl package name with mixed uppercase/lowercase
            ['com_dotnet', 'pecl', null, 'com_dotnet'],
            // pecl package name with _
            // github
            ['github:foo/bar', 'github', 'foo', 'bar'],
            // short github dsl
            ['git@github.com:foo/bar', 'github', 'foo', 'bar'],
            // long github dsl
            ['http://github.com/foo/bar', 'github', 'foo', 'bar'],
            // raw http guthub url
            ['https://github.com/foo/bar', 'github', 'foo', 'bar'],
            // raw https guthub url
            // somebody really likes to type GitHub URLs...
            ['https://www.github.com/foo/bar', 'github', 'foo', 'bar'],
            // bitbucket
            ['bitbucket:foo/bar', 'bitbucket', 'foo', 'bar'],
            // short bitbucket dsl
            ['git@bitbucket.org:foo/bar', 'bitbucket', 'foo', 'bar'],
            // long bitbucket dsl
            ['http://bitbucket.org/foo/bar', 'bitbucket', 'foo', 'bar'],
            // raw http bitbucket url
            ['https://bitbucket.org/foo/bar', 'bitbucket', 'foo', 'bar'],
            // raw https bitbucket url
            // somebody really likes to type BitBuckets URLs...
            ['http://www.bitbucket.org/foo/bar', 'bitbucket', 'foo', 'bar'],
            // user is feeling luky and finds extension that is not on github or bitbucket
            ['http://luky.feelings.org/foo/bar', 'luky', 'foo', 'bar'],
            // raw http luky url
            ['https://luky.feelings.org/foo/bar', 'luky', 'foo', 'bar'],
            // raw https luky url
            // somebody is really luky if this ext compiles...
            ['http://www.luky.feelings.org/foo/bar', 'luky', 'foo', 'bar'],
        ];
    }

    /**
     * @dataProvider dslProvider
     * @param mixed $dsl
     * @param mixed $repo
     * @param mixed $owner
     * @param mixed $package
     */
    public function test_github_dsl($dsl, $repo, $owner, $package): void
    {
        self::assertSame(
            [
                'repository' => $repo,
                'owner' => $owner,
                'package' => $package,
            ],
            $this->parser->parse($dsl)
        );
    }
}
