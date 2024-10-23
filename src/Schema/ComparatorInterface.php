<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Schema;

interface ComparatorInterface
{
    public function hasChanges(): bool;

    public function isRenamed(): bool;

    public function isPrimaryChanged(): bool;

    /**
     * @return AbstractColumn[]
     */
    public function addedColumns(): array;

    /**
     * @return AbstractColumn[]
     */
    public function droppedColumns(): array;

    /**
     * Returns array where each value contain current and initial element state.
     *
     */
    public function alteredColumns(): array;

    /**
     * @return AbstractIndex[]
     */
    public function addedIndexes(): array;

    /**
     * @return AbstractIndex[]
     */
    public function droppedIndexes(): array;

    /**
     * Returns array where each value contain current and initial element state.
     *
     */
    public function alteredIndexes(): array;

    /**
     * @return AbstractForeignKey[]
     */
    public function addedForeignKeys(): array;

    /**
     * @return AbstractForeignKey[]
     */
    public function droppedForeignKeys(): array;

    /**
     * Returns array where each value contain current and initial element state.
     *
     */
    public function alteredForeignKeys(): array;
}
