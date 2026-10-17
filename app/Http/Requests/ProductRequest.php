<?php

namespace App\Http\Requests;

use App\Support\Money;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'sku' => ['required', 'string', 'max:50', Rule::unique('products', 'sku')->ignore($this->route('product'))],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'unit_price' => ['required', 'string', fn ($attribute, $value, $fail) => Money::toCents($value) === null
                ? $fail('The unit price must be an amount like 12.50.')
                : null],
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
