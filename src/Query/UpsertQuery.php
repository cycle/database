<?php

declare(strict_types=1);

namespace Cycle\Database\Query;

use Cycle\Database\Driver\CompilerInterface;
use Cycle\Database\Injection\Parameter;

class UpsertQuery extends ActiveQuery
{
    protected string $table;
    protected array $columns   = [];
    protected array $values    = [];
    protected array $conflicts = [];

    public function __construct(?string $table = null)
    {
        $this->table = $table ?? '';
    }

    /**
     * Set upsert target table.
     *
     * @psalm-param non-empty-string $into
     */
    public function into(string $into): self
    {
        $this->table = $into;

        return $this;
    }

    /**
     * Set upsert column names. Names can be provided as array, set of parameters or comma
     * separated string.
     *
     * Examples:
     * $upsert->columns(["name", "email"]);
     * $upsert->columns("name", "email");
     * $upsert->columns("name, email");
     */
    public function columns(array|string ...$columns): self
    {
        $this->columns = $this->fetchIdentifiers($columns);

        return $this;
    }

    /**
     * Set upsert rowset values or multiple rowsets. Values can be provided in multiple forms
     * (method parameters, array of values, array of rowsets). Columns names will be automatically
     * fetched (if not already specified) from first provided rowset based on rowset keys.
     *
     * Examples:
     * $upsert->columns("name", "balance")->values("Wolfy-J", 10);
     * $upsert->values([
     *      "name" => "Wolfy-J",
     *      "balance" => 10
     * ]);
     * $upsert->values([
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
     * Set upsert conflicting index column names. Names can be provided as array, set of parameters or comma
     * separated string.
     *
     * Examples:
     * $upsert->conflicts(["identifier", "email"]);
     * $upsert->conflicts("identifier", "email");
     * $upsert->conflicts("identifier, email");
     */
    public function conflicts(array|string ...$conflicts): self
    {
        $this->conflicts = $this->fetchIdentifiers($conflicts);

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

        return \is_numeric($lastID) ? (int) $lastID : $lastID;
    }

    public function getType(): int
    {
        return CompilerInterface::UPSERT_QUERY;
    }

    public function getTokens(): array
    {
        return [
            'table'     => $this->table,
            'columns'   => $this->columns,
            'values'    => $this->values,
            'conflicts' => $this->conflicts,
        ];
    }
}
