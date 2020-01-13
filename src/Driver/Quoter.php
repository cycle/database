<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Spiral\Database\Driver;

use Spiral\Database\Exception\CompilerException;

/**
 * Responsible for table names quoting and name aliasing.
 */
final class Quoter
{
    // Used to detect functions and expression.
    private const STOPS = [')', '(', ' '];

    /** @var string */
    private $prefix;

    /** @var string */
    private $left;

    /** @var string */
    private $right;

    /** @var array */
    private $aliases = [];

    /**
     * @param string $prefix
     * @param string $quotes
     */
    public function __construct(string $prefix, string $quotes)
    {
        if (strlen($quotes) !== 2) {
            throw new CompilerException('Invalid quoter quotes, expected 2 characters string');
        }

        $this->prefix = $prefix;
        $this->left = $quotes[0];
        $this->right = $quotes[1];
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
     * Quote identifier without registering an alias.
     *
     * @param string $identifier
     * @return string
     */
    public function identifier(string $identifier): string
    {
        if ($identifier === '*') {
            return '*';
        }

        return sprintf(
            '%s%s%s',
            $this->left,
            str_replace(
                [$this->left, $this->right],
                [$this->left . $this->left, $this->right . $this->right],
                $identifier
            ),
            $this->right
        );
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
            [$identifier, $alias] = explode($matches[0], $identifier);

            return $this->aliasing($identifier, $alias, $isTable);
        }

        if ($this->hasExpressions($identifier)) {
            // processing complex expression
            return $this->expression($identifier);
        }

        if (strpos($identifier, '.') === false) {
            // no table/column pair found
            return $this->unpaired($identifier, $isTable);
        }

        // contain table.column statement (technically we can go deeper, but we should't)
        return $this->paired($identifier);
    }

    /**
     * Quoting columns and tables in complex expression.
     *
     * @param string $identifier
     * @return string
     */
    private function expression(string $identifier): string
    {
        return preg_replace_callback(
            '/([a-z][0-9_a-z\.]*\(?)/i',
            function ($match) {
                $identifier = $match[1];

                //Function name
                if ($this->hasExpressions($identifier)) {
                    return $identifier;
                }

                return $this->quote($identifier);
            },
            $identifier
        );
    }

    /**
     * Handle "IDENTIFIER AS ALIAS" expression.
     *
     * @param string $identifier
     * @param string $alias
     * @param bool   $isTable
     * @return string
     */
    private function aliasing(string $identifier, string $alias, bool $isTable): string
    {
        if (strpos($identifier, '.') !== false) {
            // non table alias
            return sprintf(
                '%s AS %s',
                $this->quote($identifier, $isTable),
                $this->identifier($alias)
            );
        }

        // never create table alias to alias associations
        $quoted = sprintf(
            '%s AS %s',
            $this->identifier($this->prefix . $identifier),
            $this->identifier($alias)
        );

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
    private function paired(string $identifier): string
    {
        //We expecting only table and column, no database name can be included (due database isolation)
        [$table, $column] = explode('.', $identifier);

        return sprintf(
            '%s.%s',
            $this->quote($table, true),
            $this->identifier($column)
        );
    }

    /**
     * Process unpaired (no . separator) identifier.
     *
     * @param string $identifier
     * @param bool   $isTable
     * @return string
     */
    private function unpaired(string $identifier, bool $isTable): string
    {
        if ($isTable && !isset($this->aliases[$identifier])) {
            $name = $this->prefix . $identifier;
            if (!isset($this->aliases[$name])) {
                //Generating our alias
                $this->registerAlias($name, $identifier);
            }

            $identifier = $name;
        }

        return $this->identifier($identifier);
    }

    /**
     * Check if string has expression markers.
     *
     * @param string $string
     * @return bool
     */
    private function hasExpressions(string $string): bool
    {
        foreach (self::STOPS as $symbol) {
            if (strpos($string, $symbol) !== false) {
                return true;
            }
        }

        return false;
    }
}
