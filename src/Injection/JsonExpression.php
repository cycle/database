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

abstract class JsonExpression implements JsonExpressionInterface
{
    protected Quoter $quoter;

    protected string $expression;

    /**
     * @var ParameterInterface[]
     */
    protected array $parameters = [];

    /**
     * @psalm-param non-empty-string $statement
     */
    public function __construct(string $statement, mixed ...$parameters)
    {
        $this->expression = $statement;

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
            'expression' => $this->compile($this->expression),
            'parameters' => $this->parameters,
        ];
    }

    public function setQuoter(Quoter $quoter): void
    {
        $this->quoter = $quoter;
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
}
