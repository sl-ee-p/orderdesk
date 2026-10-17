<?php

namespace App\Http\Requests\Api;

use App\Http\Requests\ProductRequest as WebProductRequest;
use Illuminate\Support\Arr;

/**
 * Same as the web form, except the API takes the price as integer cents.
 */
class ProductRequest extends WebProductRequest
{
    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            ...Arr::except(parent::rules(), 'unit_price'),
            'unit_price_cents' => ['required', 'integer', 'min:0', 'max:4294967295'],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function unitPriceCents(array $data): int
    {
        return (int) $data['unit_price_cents'];
    }
}
