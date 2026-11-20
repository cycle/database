<?php

declare(strict_types=1);

use Cycle\Testing\Config;

return Config::forPackage('cycle/database', root: __DIR__)
    ->unit('tests/Unit')
    ->feature('tests/Feature')
    ->build();
