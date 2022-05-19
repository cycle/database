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
        $query = 'SELECT * FROM table WHERE name = ? AND id IN(?, ?, ?) AND balance > ?';

        $parameters = [
            new Parameter('Anton'),
            1,
            2,
            3,
            new Parameter(120),
        ];

        $interpolated = Interpolator::interpolate($query, $parameters);

        $this->assertSame(
            'SELECT * FROM table WHERE name = \'Anton\' AND id IN(1, 2, 3) AND balance > 120',
            $interpolated
        );
    }

    public function testDatesInterpolation(): void
    {
        $query = 'SELECT * FROM table WHERE name = :name AND registered > :registered';

        $parameters = [
            ':name' => new Parameter('Anton'),
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

    public function testInterpolationUseNamedParameterTwice(): void
    {
        $query = 'SELECT :name as prefix, name FROM table WHERE name LIKE (CONCAT(:name, "%"))';

        $parameters = [
            ':name' => 'John',
        ];

        $interpolated = Interpolator::interpolate($query, $parameters);

        $this->assertSame(
            "SELECT 'John' as prefix, name FROM table WHERE name LIKE (CONCAT('John', \"%\"))",
            $interpolated
        );
    }

    public function testInterpolateNamedParametersWhenFirstIsPrefixOfSecond(): void
    {
        $query = 'SELECT * FROM table WHERE parameter = :parameter AND param = :param';

        $parameters = [
            'param' => 'foo',
            'parameter' => 'bar',
        ];

        $interpolated = Interpolator::interpolate($query, $parameters);

        $this->assertSame(
            "SELECT * FROM table WHERE parameter = 'bar' AND param = 'foo'",
            $interpolated
        );
    }

    public function testInterpolateParametersInStrings(): void
    {
        $query = 'SELECT \'?\' as q, "?" as qq FROM table WHERE parameter = (":param",\':param\', :param, ?)';

        $parameters = [
            'param' => 'foo',
            42,
        ];

        $interpolated = Interpolator::interpolate($query, $parameters);

        $this->assertSame(
            'SELECT \'?\' as q, "?" as qq FROM table WHERE parameter = (":param",\':param\', \'foo\', 42)',
            $interpolated
        );
    }

    public function testNestedInterpolation(): void
    {
        $query = "SELECT * FROM table WHERE name = ? AND id IN(\"in dq ?\", 'in \n\n sq ?', ?) AND balance > IN(\"in dq :p\", 'in sq :p', :p)";

        $parameters = [
            42,
            'foo',
            ':p' => 'bar',
        ];

        $interpolated = Interpolator::interpolate($query, $parameters);

        $this->assertSame(
            "SELECT * FROM table WHERE name = 42 AND id IN(\"in dq ?\", 'in \n\n sq ?', 'foo') AND balance > IN(\"in dq :p\", 'in sq :p', 'bar')",
            $interpolated
        );
    }

    public function testNamedParameterNotProvided(): void
    {
        $query = 'SELECT * FROM table WHERE name = :name';

        $parameters = ['foo'];

        $interpolated = Interpolator::interpolate($query, $parameters);

        $this->assertSame(
            'SELECT * FROM table WHERE name = :name',
            $interpolated
        );
    }

    public function testUnnamedParameterNotProvided(): void
    {
        $query = 'SELECT * FROM table WHERE name = ? OR value > ?';

        $parameters = ['foo' => 'bar'];

        $interpolated = Interpolator::interpolate($query, $parameters);

        $this->assertSame(
            'SELECT * FROM table WHERE name = ? OR value > ?',
            $interpolated
        );
    }
}
