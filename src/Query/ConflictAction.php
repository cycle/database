<?php

declare(strict_types=1);

namespace Cycle\Database\Query;

enum ConflictAction
{
    case Update;
    case Nothing;
}
