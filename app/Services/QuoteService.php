<?php

namespace App\Services;

use App\Enums\QuoteStatus;
use App\Exceptions\DomainRuleViolation;
use App\Models\Product;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class QuoteService
{
    public function __construct(private readonly TotalsCalculator $totals) {}

    /**
     * @param  array{customer_id: int, tax_rate_bp: int, valid_until?: ?string, notes?: ?string, items: list<array{product_id: int, quantity: int}>}  $data
     */
    public function create(array $data, User $user): Quote
    {
        return DB::transaction(function () use ($data, $user): Quote {
            // The real number needs the id, so insert with a unique placeholder first.
            $quote = Quote::create([
                ...$this->header($data),
                'user_id' => $user->id,
                'number' => (string) Str::uuid(),
                'status' => QuoteStatus::Draft,
            ]);

            $quote->update(['number' => sprintf('Q-%s-%05d', now()->format('Y'), $quote->id)]);
            $this->replaceItems($quote, $data['items']);

            return $quote->refresh();
        });
    }

    /**
     * @param  array{customer_id: int, tax_rate_bp: int, valid_until?: ?string, notes?: ?string, items: list<array{product_id: int, quantity: int}>}  $data
     */
    public function update(Quote $quote, array $data): Quote
    {
        return DB::transaction(function () use ($quote, $data): Quote {
            $quote = $this->lock($quote);

            if (! $quote->status->isEditable()) {
                throw DomainRuleViolation::quoteNotEditable();
            }

            $quote->update($this->header($data));
            $this->replaceItems($quote, $data['items']);

            return $quote->refresh();
        });
    }

    public function delete(Quote $quote): void
    {
        DB::transaction(function () use ($quote): void {
            if (! $this->lock($quote)->status->isEditable()) {
                throw DomainRuleViolation::quoteNotEditable();
            }

            $quote->delete();
        });
    }

    public function transition(Quote $quote, QuoteStatus $target): Quote
    {
        return DB::transaction(function () use ($quote, $target): Quote {
            $quote = $this->lock($quote);

            if (! $quote->status->canTransitionTo($target)) {
                throw DomainRuleViolation::quoteTransition($quote->status, $target);
            }

            // An expired offer can still be rejected, but no longer sent or accepted.
            if ($target !== QuoteStatus::Rejected && $quote->isExpired()) {
                throw DomainRuleViolation::quoteExpired();
            }

            $quote->update(['status' => $target]);

            return $quote;
        });
    }

    /**
     * Re-read the quote under a row lock so concurrent requests see the latest status.
     */
    private function lock(Quote $quote): Quote
    {
        return Quote::query()->lockForUpdate()->findOrFail($quote->id);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function header(array $data): array
    {
        return [
            'customer_id' => $data['customer_id'],
            'tax_rate_bp' => $data['tax_rate_bp'],
            'valid_until' => $data['valid_until'] ?? null,
            'notes' => $data['notes'] ?? null,
        ];
    }

    /**
     * Rebuild the line items from products (snapshotting name and price) and store the totals.
     * Products are re-checked here because one may be deleted or deactivated after validation.
     *
     * @param  list<array{product_id: int, quantity: int}>  $items
     */
    private function replaceItems(Quote $quote, array $items): void
    {
        $products = Product::query()
            ->where('is_active', true)
            ->findMany(array_column($items, 'product_id'))
            ->keyBy('id');

        foreach ($items as $item) {
            if (! $products->has($item['product_id'])) {
                throw DomainRuleViolation::productUnavailable();
            }
        }

        $quote->items()->delete();

        foreach ($items as $item) {
            $product = $products[$item['product_id']];

            $quote->items()->create([
                'product_id' => $product->id,
                'description' => $product->name,
                'quantity' => $item['quantity'],
                'unit_price_cents' => $product->unit_price_cents,
                'line_total_cents' => $this->totals->lineTotal($item['quantity'], $product->unit_price_cents),
            ]);
        }

        $quote->update($this->totals->totals($quote->items()->get(), $quote->tax_rate_bp));
    }
}
