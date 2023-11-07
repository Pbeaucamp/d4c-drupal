<?php

declare(strict_types=1);

use Rector\Php71\Rector\FuncCall\CountOnNullRector;
use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__ . '/src',
    ]);

    // register a single rule
    $rectorConfig->rule(CountOnNullRector::class);
};
