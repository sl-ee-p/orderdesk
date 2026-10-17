<?php

namespace App\Enums;

enum QuoteStatus: string
{
    case Draft = 'draft';
    case Sent = 'sent';
    case Accepted = 'accepted';
    case Rejected = 'rejected';

    /**
     * Statuses a quote may move to from this one.
     *
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Draft => [self::Sent],
            self::Sent => [self::Accepted, self::Rejected],
            self::Accepted, self::Rejected => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }

    /** Line items and totals may only change while the quote is a draft. */
    public function isEditable(): bool
    {
        return $this === self::Draft;
    }

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
