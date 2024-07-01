<?php

declare(strict_types=1);

namespace PhpBrew\Tests;

use PhpBrew\Testing\CommandTestCase;

/**
 * @internal
 */
class CompletionTest extends CommandTestCase
{
    /**
     * @dataProvider completionProvider
     * @param mixed $shell
     */
    public function test_completion($shell): void
    {
        $this->expectOutputString(
            file_get_contents(__DIR__ . '/../../completion/' . $shell . '/_phpbrew')
        );

        $this->app->run(['phpbrew', $shell, '--bind', 'phpbrew', '--program', 'phpbrew']);
    }

    public static function completionProvider(): iterable
    {
        return [
            'bash' => ['bash'],
            'zsh' => ['zsh'],
        ];
    }
}
