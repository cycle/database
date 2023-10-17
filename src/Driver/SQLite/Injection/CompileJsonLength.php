<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Driver\SQLite\Injection;

class CompileJsonLength extends SQLiteJsonExpression
{
    /**
     * @param non-empty-string $statement
     * @param 0|positive-int $length
     * @param non-empty-string $operator
     */
    public function __construct(
        string $statement,
        int $length,
        protected string $operator
    ) {
        parent::__construct($statement, $length);
    }

    /**
     * @param non-empty-string $statement
     *
     * @return non-empty-string
     */
    protected function compile(string $statement): string
    {
        return \sprintf(
            'json_array_length(%s%s) %s ?',
            $this->getField($statement),
            $this->getPath($statement),
            $this->operator
        );
    }
}
