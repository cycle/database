<?php

declare(strict_types=1);

namespace Cycle\Database;

interface NamedInterface
{
    /**
     * @return non-empty-string
     */
    public function getName(): string;

    /**
     * @param non-empty-string $name
     */
    public function withName(string $name): static;
}
