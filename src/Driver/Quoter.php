<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Driver;

use Cycle\Database\Exception\CompilerException;

/**
 * Responsible for table names quoting and name aliasing.
 */
final class Quoter
{
    // Used to detect functions and expression.
    private const STOPS = [')', '(', ' '];

    private string $left;
    private string $right;
    private array $aliases = [];

    /**
     * @psalm-param non-empty-string $quotes
     */
    public function __construct(
        private string $prefix,
        string $quotes,
    ) {
        \strlen($quotes) !== 2 and throw new CompilerException('Invalid quoter quotes, expected 2 characters string');

        $this->left = $quotes[0];
        $this->right = $quotes[1];
    }

    public function withPrefix(string $prefix, bool $preserveAliases = false): self
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
     * @psalm-param non-empty-string $alias
     * @psalm-param non-empty-string $identifier
     */
    public function registerAlias(string $alias, string $identifier): void
    {
        $this->aliases[$alias] = $identifier;
    }

    /**
     * Quote identifier without registering an alias.
     *
     * @psalm-param non-empty-string $identifier
     *
     * @psalm-return non-empty-string
     */
    public function identifier(string $identifier): string
    {
        if ($identifier === '*') {
            return '*';
        }

        $identifier = \sprintf(
            '%s%s%s',
            $this->left,
            \str_replace(
                [$this->left, $this->right],
                [$this->left . $this->left, $this->right . $this->right],
                $identifier,
            ),
            $this->right,
        );

        return \str_replace('.', $this->right . '.' . $this->left, $identifier);
    }

    /**
     * Query query identifier, if identified stated as table - table prefix must be added.
     *
     * @psalm-param non-empty-string $identifier Identifier can include simple column operations and functions,
     *      having "." in it will automatically force table prefix to first value.
     *
     * @param bool $isTable Set to true to let quote method know that identifier is related to table name
     *
     * @psalm-return non-empty-string
     */
    public function quote(string $identifier, bool $isTable = false): string
    {
        if (\preg_match('/ AS /i', $identifier, $matches)) {
            [$identifier, $alias] = \explode($matches[0], $identifier);

            return $this->aliasing($identifier, $alias, $isTable);
        }

        if ($this->hasExpressions($identifier)) {
            // processing complex expression
            return $this->expression($identifier);
        }

        if (!\str_contains($identifier, '.')) {
            // no table/column pair found
            return $this->unpaired($identifier, $isTable);
        }

        // contain table.column statement (technically we can go deeper, but we should't)
        return $this->paired($identifier);
    }

    /**
     * Reset aliases cache.
     */
    public function __clone()
    {
        $this->aliases = [];
    }

    /**
     * Quoting columns and tables in complex expression.
     *
     * @psalm-param non-empty-string $identifier
     */
    private function expression(string $identifier): string
    {
        return \preg_replace_callback(
            '/([_a-z][0-9_a-z\.]*\(?)/i',
            function ($match) {
                $identifier = $match[1];

                //Function name
                if ($this->hasExpressions($identifier)) {
                    return $identifier;
                }

                return $this->quote($identifier);
            },
            $identifier,
        );
    }

    /**
     * Handle "IDENTIFIER AS ALIAS" expression.
     *
     * @psalm-param non-empty-string $identifier
     * @psalm-param non-empty-string $alias
     *
     * @psalm-return non-empty-string
     */
    private function aliasing(string $identifier, string $alias, bool $isTable): string
    {
        if (\str_contains($identifier, '.')) {
            // non table alias
            return \sprintf(
                '%s AS %s',
                $this->quote($identifier, $isTable),
                $this->identifier($alias),
            );
        }

        // never create table alias to alias associations
        $quoted = \sprintf(
            '%s AS %s',
            $this->identifier($isTable ? $this->prefix . $identifier : $identifier),
            $this->identifier($alias),
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
     * @psalm-param non-empty-string $identifier
     *
     * @psalm-return non-empty-string
     */
    private function paired(string $identifier): string
    {
        //We expecting only table and column, no database name can be included (due database isolation)
        [$table, $column] = \explode('.', $identifier);

        return \sprintf(
            '%s.%s',
            $this->quote($table, true),
            $this->identifier($column),
        );
    }

    /**
     * Process unpaired (no . separator) identifier.
     *
     * @psalm-param non-empty-string $identifier
     *
     * @psalm-return non-empty-string
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
     * @psalm-param non-empty-string $string
     */
    private function hasExpressions(string $string): bool
    {
        foreach (self::STOPS as $symbol) {
            if (\str_contains($string, $symbol)) {
                return true;
            }
        }

        return false;
    }
}
