<?php

namespace PhpBrew\Extension\Provider;

class RepositoryDslParser
{
    protected static $macros = [
        'https://bitbucket.org/' => [
            'git@bitbucket.org:',
            'bitbucket:',
        ],
        'https://github.com/'    => [
            'github:',
            'git@github.com:',
        ],
    ];

    public function parse($dsl)
    {
        $ast = ['repository' => 'pecl', 'owner' => null, 'package' => $dsl];

        $url = $this->toUrl($dsl);

        // parse provider, owner and repository
        if (preg_match("#https?://(?:www\.)?([0-9a-zA-Z-_]*).+/([0-9a-zA-Z-._]*)/([0-9a-zA-Z-._]*)#", $url, $matches)) {
            $ast['repository'] = $matches[1];
            $ast['owner'] = $matches[2];
            $ast['package'] = $matches[3];
        }

        return $ast;
    }

    protected function toUrl($dsl)
    {
        $url = $dsl;
        foreach (self::$macros as $target => $sources) {
            $url = str_replace($sources, $target, $url);
        }

        return preg_replace('#\.git$#', '', $url);
    }
}
