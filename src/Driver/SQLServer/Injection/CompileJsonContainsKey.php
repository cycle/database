<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Driver\SQLServer\Injection;

class CompileJsonContainsKey extends SQLServerJsonExpression
{
    protected function compile(string $statement): string
    {
        $path = \explode('->', $statement);
        $key = $this->parseArraySyntax(\array_pop($path));

        if (\count($key) === 1) {
            return \sprintf(
                '%s IN (SELECT [key] FROM openjson(%s%s))',
                $this->quoteKey(\array_shift($key)),
                $this->getField($statement),
                $this->getPath(\implode('->', $path)),
            );
        }

        $index = \array_pop($key);
        $path[] = \array_shift($key) . \implode('', \array_map(
            static fn(string $parent): string => "[$parent]",
            $key,
        ));

        return \sprintf(
            '%s IN (SELECT [key] FROM openjson(%s%s))',
            $this->quoteKey($index),
            $this->getField($statement),
            $this->getPath(\implode('->', $path)),
        );
    }

    /**
     * The key is written into the SQL text, so apostrophes are doubled to keep it inside the string literal.
     *
     * @param non-empty-string $key
     *
     * @return non-empty-string
     */
    private function quoteKey(string $key): string
    {
        return $this->getStringLiteralPrefix() . "'" . \str_replace("'", "''", $key) . "'";
    }
}
