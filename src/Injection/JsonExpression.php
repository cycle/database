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

    public function __toString(): string
    {
        return 'exp:' . $this->expression;
    }

    public static function __set_state(array $an_array): self
    {
        return new static(
            $an_array['expression'] ?? $an_array['statement'],
            ...($an_array['parameters'] ?? [])
        );
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

    abstract protected function compile(string $statement): string;

    /**
     * @param non-empty-string $value
     * @param non-empty-string $delimiter
     *
     * @return non-empty-string
     */
    public function wrapPath(string $value, string $delimiter = '->'): string
    {
        $value = \preg_replace("/([\\\\]+)?\\'/", "''", $value);

        $segments = \explode($delimiter, $value);
        $jsonPath = \implode('.', \array_map(fn ($segment): string => $this->wrapJsonPathSegment($segment), $segments));

        return "'$" . (\str_starts_with($jsonPath, '[') ? '' : '.') . $jsonPath . "'";
    }

    /**
     * @param non-empty-string $segment
     *
     * @return non-empty-string
     */
    protected function wrapJsonPathSegment(string $segment): string
    {
        if (\preg_match('/(\[[^\]]+\])+$/', $segment, $parts)) {
            $key = \substr($segment, 0, \strpos($segment, $parts[0]));

            if (!empty($key)) {
                return '"' . $key . '"' . $parts[0];
            }

            return $parts[0];
        }

        return '"' . $segment . '"';
    }

    /**
     * @param non-empty-string $attribute
     *
     * @return array<non-empty-string>
     */
    protected function parseJsonPathArrayKeys(string $attribute): array
    {
        if (\preg_match('/(\[[^\]]+\])+$/', $attribute, $parts)) {
            $key = \substr($attribute, 0, \strpos($attribute, $parts[0]));

            \preg_match_all('/\[([^\]]+)\]/', $parts[0], $matches);
            $keys = $matches[1];

            $cleanKeys = \array_values(\array_filter($keys, static fn ($key) => $key !== ''));

            return \array_merge([$key], $cleanKeys);
        }

        return [$attribute];
    }

    protected function getQuotes(): string
    {
        return '""';
    }
}
