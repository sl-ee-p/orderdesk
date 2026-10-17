<?php

namespace Tests\Unit;

use App\Enums\OrderStatus;
use App\Enums\QuoteStatus;
use PHPUnit\Framework\TestCase;

class StatusTransitionsTest extends TestCase
{
    public function test_quote_follows_draft_sent_then_accepted_or_rejected(): void
    {
        $this->assertTrue(QuoteStatus::Draft->canTransitionTo(QuoteStatus::Sent));
        $this->assertTrue(QuoteStatus::Sent->canTransitionTo(QuoteStatus::Accepted));
        $this->assertTrue(QuoteStatus::Sent->canTransitionTo(QuoteStatus::Rejected));
        $this->assertFalse(QuoteStatus::Draft->canTransitionTo(QuoteStatus::Accepted));
        $this->assertFalse(QuoteStatus::Accepted->canTransitionTo(QuoteStatus::Draft));
    }

    public function test_only_draft_quotes_are_editable(): void
    {
        $this->assertTrue(QuoteStatus::Draft->isEditable());
        $this->assertFalse(QuoteStatus::Sent->isEditable());
    }

    public function test_order_can_be_cancelled_until_it_ships(): void
    {
        $this->assertTrue(OrderStatus::Draft->canTransitionTo(OrderStatus::Cancelled));
        $this->assertTrue(OrderStatus::Confirmed->canTransitionTo(OrderStatus::Cancelled));
        $this->assertFalse(OrderStatus::Shipped->canTransitionTo(OrderStatus::Cancelled));
    }

    public function test_order_cannot_skip_confirmation_or_leave_a_final_state(): void
    {
        $this->assertFalse(OrderStatus::Draft->canTransitionTo(OrderStatus::Shipped));
        $this->assertSame([], OrderStatus::Cancelled->allowedTransitions());
        $this->assertSame([], OrderStatus::Shipped->allowedTransitions());
    }
}
