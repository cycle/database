<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Stub;

enum FooBarEnum: string
{
    case FOO = 'foo';
    case BAR = 'bar';
    case BAZ = 'baz';
}
