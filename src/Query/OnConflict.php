<?php

declare(strict_types=1);

namespace Cycle\Database\Query;

use Cycle\Database\Exception\BuilderException;
use Cycle\Database\Injection\FragmentInterface;
use Cycle\Database\Injection\ParameterInterface;

/**
 * Immutable description of the conflict-resolution policy for an INSERT.
 *
 * Hand the configured instance to {@see InsertQuery::onConflict()}.
 * All mutating methods return a new instance.
 *
 * Base class carries only cross-driver features (target columns, action,
 * column-level update spec). Driver-specific extensions live in subclasses:
 *  - {@see OnConflictWithPredicate} — targetWhere() (index-inference predicate),
 *    shared base for the two drivers that inherit the Postgres inference clause:
 *      - {@see \Cycle\Database\Driver\Postgres\PostgresOnConflict}  — also onConstraint().
 *      - {@see \Cycle\Database\Driver\SQLite\SQLiteOnConflict}.
 *  - {@see \Cycle\Database\Driver\MySQL\MySQLOnConflict}        — withRowAlias().
 *  - {@see \Cycle\Database\Driver\SQLServer\SQLServerOnConflict} — where() (MERGE).
 */
class OnConflict
{
    /**
     * @param list<non-empty-string> $target Conflict target columns.
     * @param ConflictAction $action Resolution action.
     * @param list<non-empty-string>|array<non-empty-string, mixed>|null $update
     *        null  — overwrite every inserted column from the source row (except target columns).
     *        list  — overwrite only the listed columns from the source row.
     *        map   — column => value (scalar/Parameter/FragmentInterface) for custom expressions.
     */
    protected function __construct(
        protected array $target,
        protected ConflictAction $action,
        protected null|array $update,
    ) {}

    /**
     * Conflict target by column name(s).
     *
     * Accepts column names as variadic args, a single comma-separated string, or an array.
     */
    public static function target(string|array ...$columns): static
    {
        $resolved = self::flatten($columns);
        $resolved === [] and throw new BuilderException(
            'Conflict target must contain at least one column.',
        );

        return new static(
            target: $resolved,
            action: ConflictAction::Update,
            update: null,
        );
    }

    /**
     * Narrow a base or matching subclass instance to this type. Subclass-specific
     * fields are taken from the input if it is already of this type; otherwise
     * default values are used for those fields.
     *
     * Subclasses MUST reject driver-specific subclasses they cannot represent
     * (e.g., passing PostgresOnConflict to MySQLOnConflict::from() must throw).
     * Feature-compatible siblings, however, convert without error: PostgresOnConflict
     * and SQLiteOnConflict both understand the index-inference predicate and narrow
     * into each other (the one exception is a Postgres constraint target, which SQLite
     * cannot express).
     */
    public static function from(self $options): static
    {
        return $options;
    }

    /**
     * Set action to DO UPDATE.
     *
     * @param list<non-empty-string>|array<non-empty-string, mixed>|null $columnsOrMap
     *        null — overwrite every inserted column from the source row.
     *        list of strings — overwrite only the listed columns from the source row.
     *        column => value map — custom expressions/values per column.
     *
     * Referencing the inserted ("excluded") row inside a custom expression: use a raw
     * {@see \Cycle\Database\Injection\Fragment}, NOT an {@see \Cycle\Database\Injection\Expression}.
     * Expression quotes every identifier, and Postgres rejects the quoted "EXCLUDED"
     * pseudo-table (`missing FROM-clause entry for table "EXCLUDED"`); a Fragment is
     * emitted verbatim. The pseudo-table name is driver-specific — EXCLUDED on
     * Postgres/SQLite, the row alias (default `new_row`) on MySQL — so such expressions
     * are inherently non-portable:
     *
     *   // Postgres / SQLite
     *   ->doUpdate(['hits' => new Fragment('counters.hits + EXCLUDED.hits')])
     *   // MySQL
     *   ->doUpdate(['hits' => new Fragment('counters.hits + new_row.hits')])
     *
     * Use {@see \Cycle\Database\Injection\Expression} only for expressions over real
     * table columns (which should be quoted), not for the excluded-row reference.
     */
    public function doUpdate(?array $columnsOrMap = null): static
    {
        $clone = clone $this;
        $clone->action = ConflictAction::Update;
        $clone->update = $columnsOrMap;
        return $clone;
    }

    /**
     * Set action to DO NOTHING.
     */
    public function doNothing(): static
    {
        $clone = clone $this;
        $clone->action = ConflictAction::Nothing;
        $clone->update = null;
        return $clone;
    }

    /**
     * @return list<non-empty-string>
     */
    public function getTarget(): array
    {
        return $this->target;
    }

    public function getAction(): ConflictAction
    {
        return $this->action;
    }

    /**
     * @return list<non-empty-string>|array<non-empty-string, mixed>|null
     */
    public function getUpdate(): ?array
    {
        return $this->update;
    }

    /**
     * Stable signature of the conflict-resolution policy for {@see \Cycle\Database\Driver\CompilerCache}.
     *
     * Subclasses override to append their own fields, and push any embedded fragment
     * parameters via the provided {@see QueryParameters} bag.
     *
     * @psalm-return non-empty-string
     */
    public function getCacheKey(QueryParameters $params): string
    {
        $key = 'T' . \implode('_', $this->target) . 'A' . $this->action->name;

        if ($this->update === null) {
            return $key . 'UN';
        }

        if (\array_is_list($this->update)) {
            return $key . 'UL' . \implode('_', $this->update);
        }

        $key .= 'UM';
        foreach ($this->update as $column => $value) {
            $key .= $column . '=';

            if ($value instanceof FragmentInterface) {
                foreach ($value->getTokens()['parameters'] as $fragmentParam) {
                    $params->push($fragmentParam);
                }
                $key .= $value;
                continue;
            }

            if (!$value instanceof ParameterInterface) {
                $value = new \Cycle\Database\Injection\Parameter($value);
            }

            $params->push($value);
            $key .= 'P?';
        }

        return $key;
    }

    /**
     * Flatten variadic input from {@see self::target()} to a clean column list.
     *
     * Matches the {@see \Cycle\Database\Query\ActiveQuery::fetchIdentifiers()} convention:
     *  - a single string argument is split on commas (so `target('a, b')` → `['a', 'b']`);
     *  - inside an array argument, strings are taken literally (so `target(['a, b'])`
     *    yields a single literal column named `'a, b'`).
     *
     * In both branches values are trimmed, empty entries dropped, and non-stringable
     * values rejected with a {@see BuilderException}.
     *
     * @param array<array-key, mixed> $input
     * @return list<non-empty-string>
     */
    protected static function flatten(array $input): array
    {
        $result = [];
        foreach ($input as $item) {
            if (\is_array($item)) {
                foreach ($item as $name) {
                    self::collectLiteral($result, $name);
                }
                continue;
            }
            self::collectSplit($result, $item);
        }

        /** @var list<non-empty-string> $result */
        return $result;
    }

    /**
     * String entry: split on commas, trim parts, drop empties.
     *
     * @param list<string> $result
     */
    private static function collectSplit(array &$result, mixed $name): void
    {
        self::assertStringable($name);

        foreach (\explode(',', (string) $name) as $part) {
            $part = \trim($part);
            if ($part !== '') {
                $result[] = $part;
            }
        }
    }

    /**
     * Array entry: take the value literally — only trim and drop empties.
     *
     * @param list<string> $result
     */
    private static function collectLiteral(array &$result, mixed $name): void
    {
        self::assertStringable($name);

        $name = \trim((string) $name);
        if ($name !== '') {
            $result[] = $name;
        }
    }

    private static function assertStringable(mixed $name): void
    {
        if (!\is_string($name) && !$name instanceof \Stringable) {
            throw new BuilderException(\sprintf(
                'Conflict target column must be a string, %s given.',
                \get_debug_type($name),
            ));
        }
    }
}
