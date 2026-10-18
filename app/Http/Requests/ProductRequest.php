<?php

namespace App\Http\Requests;

use App\Support\Money;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    /** Largest price the unsigned integer price column can hold. */
    public const MAX_UNIT_PRICE_CENTS = 4294967295;

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'sku' => ['required', 'string', 'max:50', Rule::unique('products', 'sku')->ignore($this->route('product'))],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'unit_price' => ['bail', 'required', 'string', function (string $attribute, string $value, Closure $fail): void {
                $cents = Money::toCents($value);

                if ($cents === null) {
                    $fail('The unit price must be an amount like 12.50.');
                } elseif ($cents > self::MAX_UNIT_PRICE_CENTS) {
                    $fail('The unit price may not be greater than '.Money::format(self::MAX_UNIT_PRICE_CENTS).'.');
                }
            }],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * The validated input in the shape the model stores.
     *
     * @return array<string, mixed>
     */
    public function toData(): array
    {
        $data = $this->validated();

        return [
            'sku' => $data['sku'],
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'unit_price_cents' => $this->unitPriceCents($data),
            'is_active' => $this->boolean('is_active'),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function unitPriceCents(array $data): int
    {
        return Money::toCents($data['unit_price']);
    }
}
