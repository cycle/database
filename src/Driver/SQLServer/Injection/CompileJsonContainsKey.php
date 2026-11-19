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
                $this->quoteStringLiteral(\array_shift($key)),
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
            $this->quoteStringLiteral($index),
            $this->getField($statement),
            $this->getPath(\implode('->', $path)),
        );
    }
}
