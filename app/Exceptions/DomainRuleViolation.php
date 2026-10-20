<?php

namespace App\Exceptions;

use App\Enums\OrderStatus;
use App\Enums\QuoteStatus;
use DomainException;

/**
 * A business rule was broken (for example an illegal status change).
 * The message is safe to show to users; the exception handler turns it into
 * a flash message for the web UI and a 422 response for the API.
 */
class DomainRuleViolation extends DomainException
{
    public static function quoteTransition(QuoteStatus $from, QuoteStatus $to): self
    {
        return new self("A {$from->value} quote cannot become {$to->value}.");
    }

    public static function orderTransition(OrderStatus $from, OrderStatus $to): self
    {
        return new self("A {$from->value} order cannot become {$to->value}.");
    }

    public static function quoteNotEditable(): self
    {
        return new self('Only draft quotes can be changed or deleted.');
    }

    public static function quoteExpired(): self
    {
        return new self('This quote is past its valid-until date, so it can no longer be sent or accepted.');
    }

    public static function quoteNotConvertible(): self
    {
        return new self('Only accepted quotes can be converted to an order.');
    }

    public static function quoteAlreadyConverted(): self
    {
        return new self('This quote has already been converted to an order.');
    }

    public static function productUnavailable(): self
    {
        return new self('A selected product is no longer available. Please choose another one.');
    }

    public static function customerHasDocuments(): self
    {
        return new self('This customer has quotes or orders and cannot be deleted.');
    }
}
