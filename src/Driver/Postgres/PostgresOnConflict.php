<?php

declare(strict_types=1);

namespace Cycle\Database\Driver\Postgres;

use Cycle\Database\Exception\BuilderException;
use Cycle\Database\Query\ConflictAction;
use Cycle\Database\Query\OnConflict;
use Cycle\Database\Query\QueryParameters;

/**
 * Postgres-specific conflict-resolution policy.
 *
 * Adds:
 *  - {@see self::onConstraint()} — `ON CONFLICT ON CONSTRAINT <name>` target.
 *
 * Use {@see self::from()} inside the Postgres compiler to narrow a base
 * {@see OnConflict} instance into this type.
 */
final class PostgresOnConflict extends OnConflict
{
    /**
     * @param list<non-empty-string> $target
     * @param non-empty-string|null $constraint
     * @param list<non-empty-string>|array<non-empty-string, mixed>|null $update
     */
    protected function __construct(
        array $target,
        ConflictAction $action,
        null|array $update,
        protected ?string $constraint = null,
    ) {
        parent::__construct($target, $action, $update);
    }

    /**
     * Conflict target by unique-constraint name. Postgres-only feature.
     *
     * @param non-empty-string $name
     */
    public static function onConstraint(string $name): self
    {
        $name === '' and throw new BuilderException('Constraint name must not be empty.');

        return new self(
            target: [],
            action: ConflictAction::Update,
            update: null,
            constraint: $name,
        );
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

    public function getConstraint(): ?string
    {
        return $this->constraint;
    }

    public function getCacheKey(QueryParameters $params): string
    {
        $key = parent::getCacheKey($params);
        if ($this->constraint !== null) {
            $key .= 'C' . $this->constraint;
        }
        return $key;
    }
}
