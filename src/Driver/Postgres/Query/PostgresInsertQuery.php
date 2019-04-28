<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
declare(strict_types=1);

namespace Spiral\Database\Driver\Postgres\Query;

use Spiral\Database\Driver\CompilerInterface;
use Spiral\Database\Driver\Postgres\PostgresCompiler;
use Spiral\Database\Driver\Postgres\PostgresDriver;
use Spiral\Database\Exception\BuilderException;
use Spiral\Database\Query\InsertQuery;

/**
 * Postgres driver requires little bit different way to handle last insert id.
 */
class PostgresInsertQuery extends InsertQuery
{
    /**
     * {@inheritdoc}
     *
     * @throws BuilderException
     */
    public function sqlStatement(CompilerInterface $compiler = null): string
    {
        if (empty($compiler)) {
            $compiler = clone $this->compiler;
        }

        if (!$compiler instanceof PostgresCompiler) {
            throw new BuilderException('Postgres InsertQuery can be used only with Postgres driver and compiler');
        }

        /**
         * @var PostgresDriver   $driver
         * @var PostgresCompiler $compiler
         */
        return $compiler->compileInsert(
            $this->table,
            $this->columns,
            $this->rowsets,
            $this->getPrimaryKey($this->compiler->getPrefix(), $this->table)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function run()
    {
        $result = $this->driver->query($this->sqlStatement(), $this->getParameters());

        try {
            if ($this->getPrimaryKey($this->compiler->getPrefix(), $this->table) !== null) {
                return (int)$result->fetchColumn();
            }

            return null;
        } finally {
            $result->close();
        }
    }

    /**
     * @param string $prefix
     * @param string $table
     *
     * @return string|null
     */
    private function getPrimaryKey(string $prefix, string $table): ?string
    {
        if (!$this->driver instanceof PostgresDriver) {
            throw new BuilderException('Postgres InsertQuery can be used only with Postgres driver and compiler');
        }

        return $this->driver->getPrimary($prefix, $table);
    }
}
