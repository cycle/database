<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Stub;

enum FooBarEnum: string
{
    case FOO = 'foo';
    case BAR = 'bar';
    case BAZ = 'baz';
}
