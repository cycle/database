<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\MySQL\Schema;

// phpcs:ignore
use Cycle\Database\Tests\Functional\Driver\Common\Schema\IndexesTest as CommonClass;

#[\Testo\Filter\Group('driver', 'driver-mysql')]
class IndexesTest extends CommonClass
{
    public const DRIVER = 'mysql';

    #[\Testo\Test]
    public function testCreateOrderedIndex(): void
    {
        if (!$this->isOrderedIndexSupported()) {
            $this->expectExceptionMessageMatches('/column sorting is not supported$/');
        }

        parent::testCreateOrderedIndex();
    }

    #[\Testo\Test]
    public function testDropOrderedIndex(): void
    {
        if (!$this->isOrderedIndexSupported()) {
            $this->expectExceptionMessageMatches('/column sorting is not supported$/');
        }

        parent::testDropOrderedIndex();
    }

    protected function isOrderedIndexSupported(): bool
    {
        if (\getenv('MYSQL') === '5.7') {
            return false;
        }

        return \getenv('DB') !== 'mariadb';
    }
}
