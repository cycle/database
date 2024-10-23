<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Query\Traits;

use Cycle\Database\Exception\BuilderException;

trait WhereJsonTrait
{
    /**
     * Select records where the JSON value at the specified path is equal to the passed value.
     *
     * Example:
     * $select->whereJson('options->notifications->type', 'sms')
     *
     * @param non-empty-string $path
     */
    public function whereJson(string $path, mixed $value): static
    {
        $this->registerWhereJsonToken('AND', $path, $value, __FUNCTION__);
        return $this;
    }

    /**
     * Similar to the whereJson method, but uses the OR operator to combine with other conditions.
     *
     * Example:
     * $select->orWhereJson('options->notifications->type', 'sms')
     *
     * @param non-empty-string $path
     */
    public function orWhereJson(string $path, mixed $value): static
    {
        $this->registerWhereJsonToken('OR', $path, $value, __FUNCTION__);
        return $this;
    }

    /**
     * Select records where the JSON array contains the passed value.
     * The passed value can be an array, which will be encoded into JSON.
     *
     * Example:
     * $select->whereJsonContains('settings->languages', 'en')
     *
     * @param non-empty-string $path
     * @param bool $encode Encode the value into JSON.
     * @param bool $validate Check that $value is a valid JSON string if the $encode parameter is false.
     */
    public function whereJsonContains(string $path, mixed $value, bool $encode = true, bool $validate = true): static
    {
        $this->registerWhereJsonToken('AND', $path, $value, __FUNCTION__, [
            'encode' => $encode,
            'validate' => $validate,
        ]);
        return $this;
    }

    /**
     * Similar to the whereJsonContains method, but uses the OR operator to combine with other conditions.
     *
     * Example:
     * $select->orWhereJsonContains('settings->languages', 'en')
     *
     * @param non-empty-string $path
     * @param bool $encode Encode the value into JSON.
     * @param bool $validate Check that $value is a valid JSON string if the $encode parameter is false.
     */
    public function orWhereJsonContains(string $path, mixed $value, bool $encode = true, bool $validate = true): static
    {
        $this->registerWhereJsonToken('OR', $path, $value, __FUNCTION__, [
            'encode' => $encode,
            'validate' => $validate,
        ]);
        return $this;
    }

    /**
     * Select records where the JSON array doesn't contain the passed value.
     * The passed value can be an array, which will be encoded into JSON.
     *
     * Example:
     * $select->whereJsonDoesntContain('settings->languages', 'en')
     *
     * @param non-empty-string $path
     * @param bool $encode Encode the value into JSON.
     * @param bool $validate Check that $value is a valid JSON string if the $encode parameter is false.
     */
    public function whereJsonDoesntContain(string $path, mixed $value, bool $encode = true, bool $validate = true): static
    {
        $this->registerWhereJsonToken('AND', $path, $value, __FUNCTION__, [
            'encode' => $encode,
            'validate' => $validate,
        ]);
        return $this;
    }

    /**
     * Similar to the whereJsonDoesntContain method, but uses the OR operator to combine with other conditions.
     *
     * Example:
     * $select->orWhereJsonDoesntContain('settings->languages', 'en')
     *
     * @param non-empty-string $path
     * @param bool $encode Encode the value into JSON. Encode the value into JSON.
     * @param bool $validate Check that $value is a valid JSON string if the $encode parameter is false. Check that $value is a valid JSON string if the $encode parameter is false.
     */
    public function orWhereJsonDoesntContain(string $path, mixed $value, bool $encode = true, bool $validate = true): static
    {
        $this->registerWhereJsonToken('OR', $path, $value, __FUNCTION__, [
            'encode' => $encode,
            'validate' => $validate,
        ]);
        return $this;
    }

    /**
     * Select records where the JSON contains the passed path.
     *
     * Example:
     * $select->whereJsonContainsKey('options->languages->de')
     *
     * @param non-empty-string $path
     */
    public function whereJsonContainsKey(string $path): static
    {
        $this->registerWhereJsonToken('AND', $path, null, __FUNCTION__);
        return $this;
    }

    /**
     * Similar to the whereJsonContainsKey method, but uses the OR operator to combine with other conditions.
     *
     * Example:
     * $select->orWhereJsonContainsKey('options->languages->de')
     *
     * @param non-empty-string $path
     */
    public function orWhereJsonContainsKey(string $path): static
    {
        $this->registerWhereJsonToken('OR', $path, null, __FUNCTION__);
        return $this;
    }

    /**
     * Select records where the JSON doesn't contain the passed path.
     *
     * Example:
     * $select->whereJsonDoesntContainKey('options->languages->de')
     *
     * @param non-empty-string $path
     */
    public function whereJsonDoesntContainKey(string $path): static
    {
        $this->registerWhereJsonToken('AND', $path, null, __FUNCTION__);
        return $this;
    }

    /**
     * Similar to the whereJsonDoesntContainKey method, but uses the OR operator to combine with other conditions.
     *
     * Example:
     * $select->orWhereJsonDoesntContainKey('options->languages->de')
     *
     * @param non-empty-string $path
     */
    public function orWhereJsonDoesntContainKey(string $path): static
    {
        $this->registerWhereJsonToken('OR', $path, null, __FUNCTION__);
        return $this;
    }

    /**
     * Select JSON arrays by their length.
     *
     * Example:
     * $select->whereJsonLength('journal->errors', 1, '>=')
     *
     * @param non-empty-string $path
     * @param "<"|"<="|"="|">"|">=" $operator Comparison operator.
     */
    public function whereJsonLength(string $path, int $length, string $operator = '='): static
    {
        $this->registerWhereJsonToken('AND', $path, $length, __FUNCTION__, ['operator' => $operator]);
        return $this;
    }

    /**
     * Similar to the whereJsonLength method, but uses the OR operator to combine with other conditions.
     *
     * Example:
     * $select->orWhereJsonLength('journal->errors', 1, '>=')
     *
     * @param non-empty-string $path
     * @param "<"|"<="|"="|">"|">=" $operator Comparison operator.
     */
    public function orWhereJsonLength(string $path, int $length, string $operator = '='): static
    {
        $this->registerWhereJsonToken('OR', $path, $length, __FUNCTION__, ['operator' => $operator]);
        return $this;
    }

    /**
     * @param non-empty-string $path
     * @param non-empty-string $method
     * @param array<non-empty-string, mixed> $params
     */
    protected function buildJsonInjection(
        string $path,
        mixed $value,
        string $method,
        array $params,
    ): array {
        throw new BuilderException("This database engine can't handle the `$method` method.");
    }

    /**
     * Convert various amount of where function arguments into valid where token.
     *
     * @param non-empty-string $boolean Boolean joiner (AND | OR).
     * @param array $params Set of parameters collected from where functions.
     * @param array $tokens Array to aggregate compiled tokens. Reference.
     * @param callable $wrapper Callback or closure used to wrap/collect every potential parameter.
     *
     * @throws BuilderException
     */
    abstract protected function registerToken(
        string $boolean,
        array $params,
        array &$tokens,
        callable $wrapper,
    ): void;

    /**
     * @param "AND"|"OR" $operator Boolean joiner (AND | OR).
     * @param non-empty-string $path
     * @param non-empty-string $method
     */
    private function registerWhereJsonToken(
        string $operator,
        string $path,
        mixed $value,
        string $method,
        array $params = [],
    ): void {
        $this->registerToken(
            $operator,
            $this->buildJsonInjection($path, $value, $method, $params),
            $this->whereTokens,
            $this->whereWrapper(),
        );
    }
}
