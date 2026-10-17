<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\QuoteStatus;
use App\Exceptions\DomainRuleViolation;
use App\Models\Order;
use App\Models\Quote;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class QuoteConversionService
{
    /**
     * Turn an accepted quote into a draft order, copying items and totals as they are.
     * Runs in one transaction under a row lock; the unique orders.quote_id column is the final guard.
     */
    public function convert(Quote $quote): Order
    {
        return DB::transaction(function () use ($quote): Order {
            $quote = Quote::query()->lockForUpdate()->findOrFail($quote->id);

            if ($quote->status !== QuoteStatus::Accepted) {
                throw DomainRuleViolation::quoteNotConvertible();
            }

            if ($quote->order()->exists()) {
                throw DomainRuleViolation::quoteAlreadyConverted();
            }

            $order = Order::create([
                'number' => (string) Str::uuid(),
                'customer_id' => $quote->customer_id,
                'quote_id' => $quote->id,
                'status' => OrderStatus::Draft,
                'tax_rate_bp' => $quote->tax_rate_bp,
                'subtotal_cents' => $quote->subtotal_cents,
                'tax_cents' => $quote->tax_cents,
                'total_cents' => $quote->total_cents,
                'notes' => $quote->notes,
            ]);

            $order->update(['number' => sprintf('O-%s-%05d', now()->format('Y'), $order->id)]);

            foreach ($quote->items as $item) {
                $order->items()->create($item->only([
                    'product_id', 'description', 'quantity', 'unit_price_cents', 'line_total_cents',
                ]));
            }

            return $order;
        });
    }
}
