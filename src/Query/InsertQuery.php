<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Spiral\Database\Query;

use Spiral\Database\Driver\CompilerInterface;
use Spiral\Database\Exception\BuilderException;
use Spiral\Database\Injection\Parameter;

/**
 * Insert statement query builder, support singular and batch inserts.
 */
class InsertQuery extends ActiveQuery
{
    /** @var string */
    protected $table;

    /** @var array */
    protected $columns = [];

    /** @var array */
    protected $values = [];

    /**
     * @param string|null $table
     */
    public function __construct(string $table = null)
    {
        $this->table = $table ?? '';
    }

    /**
     * Set target insertion table.
     *
     * @param string $into
     * @return self
     */
    public function into(string $into): InsertQuery
    {
        $this->table = $into;

        return $this;
    }

    /**
     * Set insertion column names. Names can be provided as array, set of parameters or comma
     * separated string.
     *
     * Examples:
     * $insert->columns(["name", "email"]);
     * $insert->columns("name", "email");
     * $insert->columns("name, email");
     *
     * @param array|string $columns
     * @return self
     */
    public function columns(...$columns): InsertQuery
    {
        $this->columns = $this->fetchIdentifiers($columns);

        return $this;
    }

    /**
     * Set insertion rowset values or multiple rowsets. Values can be provided in multiple forms
     * (method parameters, array of values, array or rowsets). Columns names will be automatically
     * fetched (if not already specified) from first provided rowset based on rowset keys.
     *
     * Examples:
     * $insert->columns("name", "balance")->values("Wolfy-J", 10);
     * $insert->values([
     *      "name" => "Wolfy-J",
     *      "balance" => 10
     * ]);
     * $insert->values([
     *  [
     *      "name" => "Wolfy-J",
     *      "balance" => 10
     *  ],
     *  [
     *      "name" => "Ben",
     *      "balance" => 20
     *  ]
     * ]);
     *
     * @param mixed $rowsets
     * @return self
     */
    public function values($rowsets): InsertQuery
    {
        if (!is_array($rowsets)) {
            return $this->values(func_get_args());
        }

        if ($rowsets === []) {
            throw new BuilderException('Insert rowsets must not be empty');
        }

        //Checking if provided set is array of multiple
        reset($rowsets);

        if (!is_array($rowsets[key($rowsets)])) {
            if ($this->columns === []) {
                $this->columns = array_keys($rowsets);
            }

            $this->values[] = new Parameter(array_values($rowsets));
        } else {
            foreach ($rowsets as $values) {
                $this->values[] = new Parameter(array_values($values));
            }
        }

        return $this;
    }

    /**
     * @param QueryParameters|null $parameters
     * @return string
     */
    public function sqlStatement(QueryParameters $parameters = null): string
    {
        if ($this->values === []) {
            throw new BuilderException('Insert rowsets must not be empty');
        }

        return parent::sqlStatement($parameters);
    }

    /**
     * Run the query and return last insert id.
     *
     * @return int|string|null
     */
    public function run()
    {
        $params = new QueryParameters();
        $queryString = $this->sqlStatement($params);

        $this->driver->execute(
            $queryString,
            $params->getParameters()
        );

        $lastID = $this->driver->lastInsertID();
        if (is_numeric($lastID)) {
            return (int)$lastID;
        }

        return $lastID;
    }

    /**
     * @return int
     */
    public function getType(): int
    {
        return CompilerInterface::INSERT_QUERY;
    }

    /**
     * @return array
     */
    public function getTokens(): array
    {
        return [
            'table'   => $this->table,
            'columns' => $this->columns,
            'values'  => $this->values
        ];
    }
}
