<?php

declare(strict_types=1);

require_once __DIR__.'/vendor-bin/php-cs-fixer/vendor/fidry/php-cs-fixer-config/src/FidryConfig.php';

use Fidry\PhpCsFixerConfig\FidryConfig;
use PhpCsFixer\Finder;
use PhpCsFixer\Runner\Parallel\ParallelConfigFactory;

$finder = Finder::create()
    ->in(__DIR__)
    ->exclude([
        'dist',
        '.github',
        '.phive',
        'tools',
        'vendor',
        'vendor-bin',
    ]);

$config = new FidryConfig(null, 72000);
$config->addRules([
    // For PHP 7.2 compat
    'get_class_to_class_keyword' => false,
    'heredoc_indentation' => false,
    'trailing_comma_in_multiline' => false,
    'use_arrow_functions' => false,

    'concat_space' => false,
    'mb_str_functions' => false,
    'no_trailing_whitespace_in_string' => false,
    'yoda_style' => false,
]);
$config->setParallelConfig(ParallelConfigFactory::detect());
$config->setCacheFile(__DIR__ . '/dist/php-cs-fixer/.php-cs-fixer.cache');

return $config->setFinder($finder);
