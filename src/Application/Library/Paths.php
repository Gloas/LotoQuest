<?php

declare(strict_types=1);

namespace App\Application\Library;

/**
 * Filesystem locations used by LotoQuest.
 *
 * Both can be overridden with environment variables, which is how the
 * Docker image and the test suite isolate their data:
 *  - LOTOQUEST_LOTO_CSV : the prize sheet exported from the sorting tool
 *  - LOTOQUEST_DRAWS_DIR : where drawn numbers are stored (one subdirectory per loto)
 */
class Paths
{
    public static function lotoCsv(): string
    {
        return static::_env('LOTOQUEST_LOTO_CSV')
            ?: dirname(__DIR__, 3) . '/public/assets/loto.csv';
    }


    public static function drawsDir(): string
    {
        return rtrim(
            static::_env('LOTOQUEST_DRAWS_DIR')
                     ?: dirname(__DIR__, 3) . '/csv',
            '/'
        );
    }


    protected static function _env(string $name): string
    {
        return (string) ($_ENV[$name] ?? getenv($name) ?: '');
    }
}
