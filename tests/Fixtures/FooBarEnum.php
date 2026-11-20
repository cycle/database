<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Fixtures;

enum FooBarEnum: string
{
    case FOO = 'foo';
    case BAR = 'bar';
    case BAZ = 'baz';
}
