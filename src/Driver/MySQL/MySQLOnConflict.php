<?php

declare(strict_types=1);

namespace Cycle\Database\Driver\MySQL;

use Cycle\Database\Exception\BuilderException;
use Cycle\Database\Query\ConflictAction;
use Cycle\Database\Query\OnConflict;
use Cycle\Database\Query\QueryParameters;

/**
 * MySQL-specific conflict-resolution policy.
 *
 * Adds:
 *  - {@see self::withRowAlias()} — customize the row alias used in
 *    `INSERT ... AS <alias> ON DUPLICATE KEY UPDATE col = <alias>.col`.
 *    Default alias is {@see self::DEFAULT_ROW_ALIAS}. Customize only when it
 *    collides with a real column name your update expressions reference.
 *
 * Note on target columns: MySQL's `ON DUPLICATE KEY UPDATE` fires on ANY matching
 * unique index, so the {@see self::target()} list does NOT drive which constraint
 * is checked. It IS however used by the compiler to compute the auto-update list
 * when {@see self::doUpdate()} is called without an explicit column list — target
 * columns are excluded from the auto-generated `SET col = new_row.col` clause,
 * matching Postgres/SQLite semantics.
 */
final class MySQLOnConflict extends OnConflict
{
    public const DEFAULT_ROW_ALIAS = 'new_row';

    /**
     * @param list<non-empty-string> $target
     * @param list<non-empty-string>|array<non-empty-string, mixed>|null $update
     * @param non-empty-string $rowAlias
     */
    protected function __construct(
        array $target,
        ConflictAction $action,
        null|array $update,
        protected string $rowAlias = self::DEFAULT_ROW_ALIAS,
    ) {
        parent::__construct($target, $action, $update);
    }

    public static function from(OnConflict $options): static
    {
        if ($options instanceof self) {
            return $options;
        }

        if ($options::class !== OnConflict::class) {
            throw new BuilderException(\sprintf(
                'Cannot narrow %s to %s. Use the base OnConflict, or %s directly.',
                $options::class,
                self::class,
                self::class,
            ));
        }

        return new self(
            target: $options->getTarget(),
            action: $options->getAction(),
            update: $options->getUpdate(),
        );
    }

    /**
     * Set the row alias used in `INSERT ... AS <alias> ON DUPLICATE KEY UPDATE`.
     *
     * @param non-empty-string $alias Must be a valid MySQL identifier.
     */
    public function withRowAlias(string $alias): self
    {
        $alias === '' and throw new BuilderException('Row alias must not be empty.');

        $clone = clone $this;
        $clone->rowAlias = $alias;
        return $clone;
    }

    /**
     * @return non-empty-string
     */
    public function getRowAlias(): string
    {
        return $this->rowAlias;
    }

    public function getCacheKey(QueryParameters $params): string
    {
        return parent::getCacheKey($params) . 'RA' . $this->rowAlias;
    }
}
