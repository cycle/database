<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Injection;

use Cycle\Database\Driver\CompilerInterface;
use Cycle\Database\Driver\Quoter;
use Cycle\Database\Exception\DriverException;

abstract class JsonExpression implements FragmentInterface
{
    protected string $expression;
    protected Quoter $quoter;

    /**
     * @var ParameterInterface[]
     */
    protected array $parameters = [];

    /**
     * @psalm-param non-empty-string $statement
     */
    public function __construct(string $statement, mixed ...$parameters)
    {
        $this->quoter = new Quoter('', $this->getQuotes());

        $this->expression = $this->compile($statement);

        foreach ($parameters as $parameter) {
            if ($parameter instanceof ParameterInterface) {
                $this->parameters[] = $parameter;
            } else {
                $this->parameters[] = new Parameter($parameter);
            }
        }
    }

    public function getType(): int
    {
        return CompilerInterface::JSON_EXPRESSION;
    }

    public function getTokens(): array
    {
        return [
            'expression' => $this->expression,
            'parameters' => $this->parameters,
        ];
    }

    public function __toString(): string
    {
        return 'exp:' . $this->expression;
    }

    public static function __set_state(array $an_array): self
    {
        return new static(
            $an_array['expression'] ?? $an_array['statement'],
            ...($an_array['parameters'] ?? []),
        );
    }

    /**
     * @param non-empty-string $statement
     *
     * @return non-empty-string
     */
    abstract protected function compile(string $statement): string;

    /**
     * @param non-empty-string $statement
     *
     * @return non-empty-string
     */
    protected function getField(string $statement): string
    {
        $parts = \explode('->', $statement, 2);

        return $this->quoter->quote($parts[0]);
    }

    /**
     * @param non-empty-string $statement
     *
     * @return non-empty-string
     */
    protected function getPath(string $statement): string
    {
        $parts = \explode('->', $statement, 2);

        return \count($parts) > 1 ? ', ' . $this->wrapPath($parts[1]) : '';
    }

    /**
     * Parses a string with array access syntax (e.g., "field[array-key]") and extracts the field name and key.
     *
     * @param non-empty-string $path
     *
     * @return array<non-empty-string>
     */
    protected function parseArraySyntax(string $path): array
    {
        if (\preg_match('/(\[[^\]]+\])+$/', $path, $parts)) {
            $parsed = [\trim(\substr($path, 0, \strpos($path, $parts[0])))];

            \preg_match_all('/\[([^\]]+)\]/', $parts[0], $matches);

            foreach ($matches[1] as $key) {
                if (\trim($key) === '') {
                    throw new DriverException('Invalid JSON array path syntax. Array key must not be empty.');
                }
                $parsed[] = $key;
            }

            return $parsed;
        }

        if (\str_contains($path, '[') && \str_contains($path, ']')) {
            throw new DriverException(
                'Unable to parse array path syntax. Array key must be wrapped in square brackets.',
            );
        }

        return [$path];
    }

    /**
     * @return non-empty-string
     */
    protected function getQuotes(): string
    {
        return '""';
    }

    /**
     * Transforms a string like "options->languages" into a correct path like $."options"."languages".
     *
     * @param non-empty-string $value
     * @param non-empty-string $delimiter
     *
     * @return non-empty-string
     */
    private function wrapPath(string $value, string $delimiter = '->'): string
    {
        $value = \preg_replace("/(\\+)?'/", "''", $value);

        $segments = \explode($delimiter, $value);
        $path = \implode('.', \array_map(fn(string $segment): string => $this->wrapPathSegment($segment), $segments));

        return "'$" . (\str_starts_with($path, '[') ? '' : '.') . $path . "'";
    }

    /**
     * @param non-empty-string $segment
     *
     * @return non-empty-string
     */
    private function wrapPathSegment(string $segment): string
    {
        $parts = $this->parseArraySyntax($segment);

        if (isset($parts[1])) {
            return \sprintf('"%s"[%s]', $parts[0], $parts[1]);
        }

        return \sprintf('"%s"', $segment);
    }
}
