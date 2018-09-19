<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */
namespace Spiral\Database\Tests\MySQL;

class SelectQueryTest extends \Spiral\Database\Tests\SelectQueryTest
{
    use DriverTrait;

    public function testOffsetNoLimit()
    {
        $select = $this->database->select()->from(['users'])->offset(20);

        $this->assertSameQuery(
            "SELECT * FROM {users} LIMIT 18446744073709551615 OFFSET 20",
            $select
        );
    }

    public function testSelectWithSimpleWhereNull()
    {
        $select = $this->database->select()->distinct()->from(['users'])->where('name', null);

        $this->assertSameQuery(
            "SELECT DISTINCT * FROM {users} WHERE {name} IS ?",
            $select
        );
    }

    public function testSelectWithSimpleWhereNotNull()
    {
        $select = $this->database->select()->distinct()->from(['users'])->where('name', '!=', null);

        $this->assertSameQuery(
            "SELECT DISTINCT * FROM {users} WHERE {name} IS NOT ?",
            $select
        );
    }
}