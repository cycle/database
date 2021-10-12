<?php

declare(strict_types=1);

namespace Cycle\Database\Driver\Oracle;

use Cycle\Database\Driver\Driver;
use Cycle\Database\Exception\StatementException;
use Cycle\Database\Query\QueryBuilder;
use Throwable;

class OracleDriver extends Driver
{
    /**
     * Default public schema name for all postgres connections.
     *
     * @var non-empty-string
     */
    public const PUBLIC_SCHEMA = 'SYSTEM';

    /**
     * Option key for all available postgres schema names.
     *
     * @var non-empty-string
     */
    private const OPT_AVAILABLE_SCHEMAS = 'schema';

    /**
     * Schemas to search tables in
     *
     * @var string[]
     * @psalm-var non-empty-array<non-empty-string>
     */
    private array $searchSchemas = [];

    /**
     * @param array $options
     */
    public function __construct(array $options)
    {
        parent::__construct(
            $options,
            new OracleHandler(),
            new OracleCompiler('""'),
            QueryBuilder::defaultBuilder()
        );

        $this->defineSchemas($this->options);
    }

    /**
     * @inheritDoc
     */
    public function getType(): string
    {
        return 'Oracle';
    }

    /**
     * @inheritDoc
     */
    public function getSource(): string
    {
        // remove "oci:"
        return substr($this->getDSN(), 4);
    }

    /**
     * Schemas to search tables in
     *
     * @return string[]
     */
    public function getSearchSchemas(): array
    {
        return $this->searchSchemas;
    }

    /**
     * Check if schemas are defined
     *
     * @return bool
     */
    public function shouldUseDefinedSchemas(): bool
    {
        return $this->searchSchemas !== [];
    }

    /**
     * Parse the table name and extract the schema and table.
     *
     * @param  string  $name
     * @return string[]
     */
    public function parseSchemaAndTable(string $name): array
    {
        $schema = null;
        $table = $name;

        if (str_contains($name, '.')) {
            [$schema, $table] = explode('.', $name, 2);

            if ($schema === '$user') {
                $schema = $this->options['username'];
            }
        }

        return [$schema ?? $this->searchSchemas[0], $table];
    }

    /**
     * @inheritDoc
     */
    protected function mapException(Throwable $exception, string $query): StatementException
    {
        if ((int)$exception->getCode() === 23000) {
            return new StatementException\ConstrainException($exception, $query);
        }

        return new StatementException($exception, $query);
    }

    /**
     * Define schemas from config
     */
    private function defineSchemas(array $options): void
    {
        $options[self::OPT_AVAILABLE_SCHEMAS] = (array)($options[self::OPT_AVAILABLE_SCHEMAS] ?? []);

        $defaultSchema = static::PUBLIC_SCHEMA;

        $this->searchSchemas = array_values(array_unique(
            [$defaultSchema, ...$options[self::OPT_AVAILABLE_SCHEMAS]]
        ));
    }
}
