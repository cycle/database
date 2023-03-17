<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Config;

use PHPUnit\Framework\TestCase;

abstract class BaseConfigTest extends TestCase
{
    public function portDataProvider(): \Traversable
    {
        yield [3306, 3306];
        yield ['3306', 3306];
        yield [0, 0];
        yield ['foo', 0];
    }
}
