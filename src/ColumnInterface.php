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
 * Represents table schema column abstraction.
 */
interface ColumnInterface
{
    /**
     * PHP types for phpType() method.
     */
    public const INT = 'int';

    public const BOOL = 'bool';
    public const STRING = 'string';
    public const FLOAT = 'float';

    /**
     * Get element name (unquoted).
     *
     * @return non-empty-string
     */
    public function getName(): string;

    /**
     * Internal database type, can vary based on database driver.
     */
    public function getInternalType(): string;

    /**
     * DBMS specific reverse mapping must map database specific type into limited set of abstract
     * types. Value depends on driver implementation.
     */
    public function getAbstractType(): string;

    /**
     * Must return PHP type column value can be better mapped into: int, bool, string or float.
     */
    public function getType(): string;

    /**
     * Column size.
     */
    public function getSize(): int;

    /**
     * Column precision.
     */
    public function getPrecision(): int;

    /**
     * Column scale value.
     */
    public function getScale(): int;

    /**
     * Can column store null value?
     */
    public function isNullable(): bool;

    /**
     * Indication that column has default value.
     */
    public function hasDefaultValue(): bool;

    /**
     * Get column default value, value must be automatically converted to appropriate internal type.
     */
    public function getDefaultValue(): mixed;
}
