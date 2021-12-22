<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database;

/**
 * Represents single foreign key and it's options.
 */
interface ForeignKeyInterface
{
    public const CASCADE = 'CASCADE';
    public const NO_ACTION = 'NO ACTION';

    /**
     * Get element name (unquoted).
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Get column name foreign key assigned to.
     *
     * @return array
     */
    public function getColumns(): array;

    /**
     * Foreign table name.
     *
     * @return string
     */
    public function getForeignTable(): string;

    /**
     * Foreign key (column name).
     *
     * @return array
     */
    public function getForeignKeys(): array;

    /**
     * Get delete rule, possible values: NO ACTION, CASCADE and etc.
     *
     * @return string
     */
    public function getDeleteRule(): string;

    /**
     * Get update rule, possible values: NO ACTION, CASCADE and etc.
     *
     * @return string
     */
    public function getUpdateRule(): string;
}
