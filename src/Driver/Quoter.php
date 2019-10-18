<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Spiral\Database\Driver;

/**
 * Class responsible for "intelligent" table and column name quoting.
 * Attention, Quoter does not support string literals at this moment, use FragmentInterface.
 */
final class Quoter
{
    // Used to detect functions and expression.
    private const STOPS = [')', '(', ' '];

    /** @var array */
    private $aliases = [];

    /** @var DriverInterface */
    private $driver = null;

    /** @var string */
    private $prefix = '';

    /**
     * @param DriverInterface $driver Driver needed to correctly quote identifiers and string
     *                                quotes.
     * @param string          $prefix
     */
    public function __construct(DriverInterface $driver, string $prefix)
    {
        $this->driver = $driver;
        $this->prefix = $prefix;
    }

    /**
     * Reset aliases cache.
     */
    public function __clone()
    {
        $this->aliases = [];
    }

    /**
     * @param string $prefix
     * @param bool   $preserveAliases
     * @return Quoter
     */
    public function withPrefix(string $prefix, bool $preserveAliases = false): Quoter
    {
        $quoter = clone $this;
        $quoter->prefix = $prefix;

        if ($preserveAliases) {
            $quoter->aliases = $this->aliases;
        }

        return $quoter;
    }

    /**
     * @return string
     */
    public function getPrefix(): string
    {
        return $this->prefix;
    }

    /**
     * Register new quotation alias.
     *
     * @param string $alias
     * @param string $identifier
     */
    public function registerAlias(string $alias, string $identifier): void
    {
        $this->aliases[$alias] = $identifier;
    }

    /**
     * Query query identifier, if identified stated as table - table prefix must be added.
     *
     * @param string $identifier Identifier can include simple column operations and functions,
     *                           having "." in it will automatically force table prefix to first
     *                           value.
     * @param bool   $isTable    Set to true to let quote method know that identifier is related to
     *                           table name.
     * @return mixed|string
     */
    public function quote(string $identifier, bool $isTable = false): string
    {
        if (preg_match('/ AS /i', $identifier, $matches)) {
            list($identifier, $alias) = explode($matches[0], $identifier);

            return $this->aliasing($identifier, $alias, $isTable);
        }

        if ($this->hasExpressions($identifier)) {
            //Processing complex expression
            return $this->expression($identifier);
        }

        if (strpos($identifier, '.') === false) {
            //No table/column pair found
            return $this->unpaired($identifier, $isTable);
        }

        //Contain table.column statement (technically we can go deeper, but we should't)
        return $this->paired($identifier);
    }

    /**
     * Quoting columns and tables in complex expression.
     *
     * @param string $identifier
     * @return string
     */
    protected function expression(string $identifier): string
    {
        return preg_replace_callback('/([a-z][0-9_a-z\.]*\(?)/i', function ($match) {
            $identifier = $match[1];

            //Function name
            if ($this->hasExpressions($identifier)) {
                return $identifier;
            }

            return $this->quote($identifier);
        }, $identifier);
    }

    /**
     * Handle "IDENTIFIER AS ALIAS" expression.
     *
     * @param string $identifier
     * @param string $alias
     * @param bool   $isTable
     * @return string
     */
    protected function aliasing(string $identifier, string $alias, bool $isTable): string
    {
        if (strpos($identifier, '.') !== false) {
            // non table alias
            return $this->quote($identifier, $isTable) . ' AS ' . $this->driver->identifier($alias);
        }

        // never create table alias to alias associations
        $quoted = $this->driver->identifier($this->prefix . $identifier) . ' AS ' . $this->driver->identifier($alias);

        if ($isTable) {
            //We have to apply operation post factum to prevent self aliasing (name AS name)
            //when db has prefix, expected: prefix_name as name)
            $this->registerAlias($alias, $identifier);
        }

        return $quoted;
    }

    /**
     * Processing pair of table and column.
     *
     * @param string $identifier
     * @return string
     */
    protected function paired(string $identifier): string
    {
        //We expecting only table and column, no database name can be included (due database isolation)
        list($table, $column) = explode('.', $identifier);

        return "{$this->quote($table, true)}.{$this->driver->identifier($column)}";
    }

    /**
     * Process unpaired (no . separator) identifier.
     *
     * @param string $identifier
     * @param bool   $isTable
     * @return string
     */
    protected function unpaired(string $identifier, bool $isTable): string
    {
        if ($isTable && !isset($this->aliases[$identifier])) {
            if (!isset($this->aliases[$this->prefix . $identifier])) {
                //Generating our alias
                $this->registerAlias($this->prefix . $identifier, $identifier);
            }

            $identifier = $this->prefix . $identifier;
        }

        return $this->driver->identifier($identifier);
    }

    /**
     * Check if string has expression markers.
     *
     * @param string $string
     * @return bool
     */
    protected function hasExpressions(string $string): bool
    {
        foreach (self::STOPS as $symbol) {
            if (strpos($string, $symbol) !== false) {
                return true;
            }
        }

        return false;
    }
}
