<?php

declare(strict_types=1);

namespace Laratusk\SharedJobs\Enums;

enum Role: string
{
    case Dispatcher = 'dispatcher';
    case Consumer = 'consumer';
    case Both = 'both';

    public function canDispatch(): bool
    {
        return match ($this) {
            self::Dispatcher, self::Both => true,
            self::Consumer => false,
        };
    }

    public function canConsume(): bool
    {
        return match ($this) {
            self::Consumer, self::Both => true,
            self::Dispatcher => false,
        };
    }
}
