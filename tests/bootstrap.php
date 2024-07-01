<?php

use PHPUnit\Framework\TestCase;
use VCR\VCR;

require __DIR__ . '/../vendor/autoload.php';

if (!class_exists('PHPUnit_Framework_TestCase')) {
    class_alias(TestCase::class, 'PHPUnit_Framework_TestCase');
}

VCR::configure()
  ->setCassettePath('tests/fixtures/vcr_cassettes')
  ->enableLibraryHooks(['curl', 'stream_wrapper'])
  ->setStorage('json');
