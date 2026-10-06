<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

// Never let the test suite touch the real prize sheet or the real draws.
$_ENV['LOTOQUEST_LOTO_CSV'] = __DIR__ . '/fixtures/loto.csv';
putenv('LOTOQUEST_LOTO_CSV=' . $_ENV['LOTOQUEST_LOTO_CSV']);
