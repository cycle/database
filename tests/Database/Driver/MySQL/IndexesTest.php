<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Spiral\Database\Tests\Driver\MySQL;

class IndexesTest extends \Spiral\Database\Tests\IndexesTest
{
    public const DRIVER = 'mysql';

    public function testCreateOrderedIndex(): void
    {
        if (getenv('DB') === 'mariadb') {
            $this->expectExceptionMessageRegExp('/column sorting is not supported$/');
        }

        parent::testCreateOrderedIndex();
    }

    public function testDropOrderedIndex(): void
    {
        if (getenv('DB') === 'mariadb') {
            $this->expectExceptionMessageRegExp('/column sorting is not supported$/');
        }

        parent::testDropOrderedIndex();
    }
}
