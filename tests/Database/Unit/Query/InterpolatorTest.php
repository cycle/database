<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Query;

use PHPUnit\Framework\TestCase;
use Cycle\Database\Injection\Parameter;
use Cycle\Database\Query\Interpolator;

class InterpolatorTest extends TestCase
{
    public function testInterpolation(): void
    {
        $query = 'SELECT * FROM table WHERE name = ? AND id IN(?, ?, ?) AND balance > :balance';

        $parameters = [
            new Parameter('Anton?'),
            1,
            2,
            3,
            ':balance' => 120,
        ];

        $interpolated = Interpolator::interpolate($query, $parameters);

        $this->assertSame(
            'SELECT * FROM table WHERE name = \'Anton?\' AND id IN(1, 2, 3) AND balance > 120',
            $interpolated
        );
    }

    public function testInterpolationUseNamedParameterTwice(): void
    {
        $query = 'SELECT :name as prefix, name FROM table WHERE name LIKE (CONCAT(:name, "%"))';

        $parameters = [
            ':name' => 'John',
        ];

        $interpolated = Interpolator::interpolate($query, $parameters);

        $this->assertSame(
            "SELECT 'John' as prefix, name FROM table WHERE name LIKE (CONCAT('John', '%'))",
            $interpolated
        );
    }

    public function testInterpolationCheckNamedParametersWhenFirstIsPrefixOfSecond(): void
    {
        $query = 'SELECT * FROM table WHERE parameter = :parameter AND param = :param';

        $parameters = [
            'param' => 'foo',
            'parameter' => 'bar',
        ];

        $interpolated = Interpolator::interpolate($query, $parameters);

        $this->assertSame(
            "SELECT * FROM table WHERE parameter = 'foo' AND param = 'bar'",
            $interpolated
        );
    }

    public function testDatesInterpolation(): void
    {
        $query = 'SELECT * FROM table WHERE name = :name AND registered > :registered';

        $parameters = [
            'name' => 'Anton',
            ':registered' => new Parameter($date = new \DateTime('now')),
        ];

        $interpolated = Interpolator::interpolate($query, $parameters);

        $this->assertSame(
            'SELECT * FROM table WHERE name = \'Anton\' AND registered > \''
            . $date->format(\DateTime::ATOM) . '\'',
            $interpolated
        );
    }

    public function testDateInterpolationWithDateTimeImmutable(): void
    {
        $query = 'SELECT * FROM table WHERE name = :name AND registered > :registered';

        $parameters = [
            ':name' => new Parameter('Anton'),
            ':registered' => new Parameter($date = new \DateTimeImmutable('now')),
        ];

        $interpolated = Interpolator::interpolate($query, $parameters);

        $this->assertSame(
            'SELECT * FROM table WHERE name = \'Anton\' AND registered > \''
            . $date->format(\DateTime::ATOM)
            . '\'',
            $interpolated
        );
    }
}
