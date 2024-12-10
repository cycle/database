<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Query;

use Cycle\Database\Driver\CompilerInterface;
use Cycle\Database\Injection\Parameter;

/**
 * Insert statement query builder, support singular and batch inserts.
 */
class InsertQuery extends ActiveQuery
{
    protected string $table;
    protected array $columns = [];
    protected array $values = [];

    public function __construct(?string $table = null)
    {
        $this->table = $table ?? '';
    }

    /**
     * Set target insertion table.
     *
     * @psalm-param non-empty-string $into
     */
    public function into(string $into): self
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
     */
    public function columns(array|string ...$columns): self
    {
        $this->columns = $this->fetchIdentifiers($columns);

        return $this;
    }

    /**
     * Set insertion rowset values or multiple rowsets. Values can be provided in multiple forms
     * (method parameters, array of values, array of rowsets). Columns names will be automatically
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
     */
    public function values(mixed $rowsets): self
    {
        if (!\is_array($rowsets)) {
            return $this->values(\func_get_args());
        }

        if ($rowsets === []) {
            return $this;
        }

        //Checking if provided set is array of multiple
        \reset($rowsets);

        if (!\is_array($rowsets[\key($rowsets)])) {
            if ($this->columns === []) {
                $this->columns = \array_keys($rowsets);
            }

            $this->values[] = new Parameter(\array_values($rowsets));
        } else {
            if ($this->columns === []) {
                $this->columns = \array_keys($rowsets[\key($rowsets)]);
            }

            foreach ($rowsets as $values) {
                $this->values[] = new Parameter(\array_values($values));
            }
        }

        return $this;
    }

    /**
     * Run the query and return last insert id.
     * Returns an assoc array of values if multiple columns were specified as returning columns.
     *
     * @return array<non-empty-string, mixed>|int|non-empty-string|null
     */
    public function run(): mixed
    {
        $params = new QueryParameters();
        $queryString = $this->sqlStatement($params);

        $this->driver->execute(
            $queryString,
            $params->getParameters(),
        );

        $lastID = $this->driver->lastInsertID();
        if (\is_numeric($lastID)) {
            return (int) $lastID;
        }

        return $lastID;
    }

    public function getType(): int
    {
        return CompilerInterface::INSERT_QUERY;
    }

    public function getTokens(): array
    {
        return [
            'table'   => $this->table,
            'columns' => $this->columns,
            'values'  => $this->values,
        ];
    }
}
