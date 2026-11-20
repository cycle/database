<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Feature\MySQL\Schema;

use Cycle\Database\Tests\Feature\Common\Schema\IndexesTest as CommonClass;
use Cycle\Testing\Database\Attribute\OnlyDriver;
use Cycle\Testing\Database\Driver;
use Testo\Expect;
use Testo\Test;

#[OnlyDriver(Driver::MySQL)]
final class IndexesTest extends CommonClass
{
    #[Test]
    public function testCreateOrderedIndex(): void
    {
        if (!$this->isOrderedIndexSupported()) {
            Expect::exception(\Throwable::class)->withMessageMatchingRegex('/column sorting is not supported$/');
        }

        parent::testCreateOrderedIndex();
    }

    #[Test]
    public function testDropOrderedIndex(): void
    {
        if (!$this->isOrderedIndexSupported()) {
            Expect::exception(\Throwable::class)->withMessageMatchingRegex('/column sorting is not supported$/');
        }

        parent::testDropOrderedIndex();
    }

    protected function isOrderedIndexSupported(): bool
    {
        $version = $this->serverVersion();

        return !\str_starts_with($version, '5.7') && !\str_contains(\strtolower($version), 'mariadb');
    }
}
