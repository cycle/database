<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Stub;

enum IntegerEnum: int
{
    case ANSWER = 42;
    case ZERO = 0;
    case TEN = 10;
    case HUNDRED = 100;
}
