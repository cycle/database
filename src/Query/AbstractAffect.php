<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Database\Query;

use Spiral\Database\Driver;
use Spiral\Database\QueryCompiler;

/**
 * Generic prototype for affect queries with WHERE and JOIN supports. At this moment used as parent
 * for delete and update query builders.
 */
abstract class AbstractAffect extends AbstractWhere
{
    /**
     * Every affect builder must be associated with specific table.
     *
     * @var string
     */
    protected $table = '';

    /**
     * {@inheritdoc}
     *
     * @param string $table Associated table name.
     * @param array  $where Initial set of where rules specified as array.
     */
    public function __construct(
        Driver $driver,
        QueryCompiler $compiler,
        string $table = '',
        array $where = []
    ) {
        parent::__construct($driver, $compiler);

        $this->table = $table;

        if (!empty($where)) {
            $this->where($where);
        }
    }

    /**
     * {@inheritdoc}
     *
     * Affect queries will return count of affected rows.
     *
     * @return int
     */
    public function run(): int
    {
        return $this->pdoStatement()->rowCount();
    }
}
