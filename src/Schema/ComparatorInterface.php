<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Spiral\Database\Schema;

interface ComparatorInterface
{
    /**
     * @return bool
     */
    public function hasChanges(): bool;

    /**
     * @return bool
     */
    public function isRenamed(): bool;

    /**
     * @return bool
     */
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
     * @return array
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
     * @return array
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
     * @return array
     */
    public function alteredForeignKeys(): array;
}
