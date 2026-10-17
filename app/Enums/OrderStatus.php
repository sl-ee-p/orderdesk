<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Draft = 'draft';
    case Confirmed = 'confirmed';
    case Shipped = 'shipped';
    case Cancelled = 'cancelled';

    /**
     * Statuses an order may move to from this one.
     *
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Draft => [self::Confirmed, self::Cancelled],
            self::Confirmed => [self::Shipped, self::Cancelled],
            self::Shipped, self::Cancelled => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
