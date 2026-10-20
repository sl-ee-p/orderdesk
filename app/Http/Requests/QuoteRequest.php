<?php

namespace App\Http\Requests;

use App\Support\Money;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class QuoteRequest extends FormRequest
{
    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'integer', Rule::exists('customers', 'id')],
            'tax_rate' => ['bail', 'required', 'string', function (string $attribute, string $value, Closure $fail): void {
                $basisPoints = Money::percentToBasisPoints($value);

                if ($basisPoints === null || $basisPoints > 10000) {
                    $fail('The tax rate must be a percentage between 0 and 100.');
                }
            }],
            'valid_until' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.product_id' => ['required', 'integer', Rule::exists('products', 'id')->where('is_active', true)],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:100000'],
        ];
    }

    /**
     * The validated input in the shape QuoteService expects.
     *
     * @return array{customer_id: int, tax_rate_bp: int, valid_until: ?string, notes: ?string, items: list<array{product_id: int, quantity: int}>}
     */
    public function toData(): array
    {
        $data = $this->validated();

        return [
            'customer_id' => (int) $data['customer_id'],
            'tax_rate_bp' => $this->taxRateBasisPoints($data),
            'valid_until' => $data['valid_until'] ?? null,
            'notes' => $data['notes'] ?? null,
            'items' => array_map(
                fn (array $item) => ['product_id' => (int) $item['product_id'], 'quantity' => (int) $item['quantity']],
                array_values($data['items']),
            ),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function taxRateBasisPoints(array $data): int
    {
        return Money::percentToBasisPoints($data['tax_rate']);
    }
}
