<?php
declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Database;

/**
 * Represents single foreign key and it's options.
 */
interface ForeignKeyInterface
{
    const CASCADE   = 'CASCADE';
    const NO_ACTION = 'NO ACTION';

    /**
     * Get element name (unquoted).
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Get column name foreign key assigned to.
     *
     * @return string
     */
    public function getColumn(): string;

    /**
     * Foreign table name.
     *
     * @return string
     */
    public function getForeignTable(): string;

    /**
     * Foreign key (column name).
     *
     * @return string
     */
    public function getForeignKey(): string;

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
