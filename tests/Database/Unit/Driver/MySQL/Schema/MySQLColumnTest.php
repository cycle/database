<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Driver\MySQL\Schema;

use Cycle\Database\Driver\MySQL\Schema\MySQLColumn;
use Cycle\Database\Injection\Fragment;
use PHPUnit\Framework\TestCase;

final class MySQLColumnTest extends TestCase
{
    public static function currentTimestampDefaultProvider(): iterable
    {
        yield 'MySQL' => ['datetime', 'CURRENT_TIMESTAMP'];
        yield 'MySQL with fsp' => ['datetime(6)', 'CURRENT_TIMESTAMP(6)'];
        yield 'MySQL timestamp with fsp' => ['timestamp(3)', 'CURRENT_TIMESTAMP(3)'];
        yield 'MariaDB' => ['datetime', 'current_timestamp()'];
        yield 'MariaDB with fsp' => ['datetime(6)', 'current_timestamp(6)'];
    }

    /**
     * @dataProvider currentTimestampDefaultProvider
     */
    public function testReflectedCurrentTimestampDefault(string $type, string $default): void
    {
        $column = MySQLColumn::createInstance('table', [
            'Field' => 'target',
            'Type' => $type,
            'Comment' => '',
            'Null' => 'NO',
            'Default' => $default,
            'Extra' => 'DEFAULT_GENERATED',
        ]);

        $this->assertEquals(new Fragment(MySQLColumn::DATETIME_NOW), $column->getDefaultValue());
    }
}
