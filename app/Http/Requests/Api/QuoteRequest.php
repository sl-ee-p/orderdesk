<?php

namespace App\Http\Requests\Api;

use App\Http\Requests\QuoteRequest as WebQuoteRequest;
use Illuminate\Support\Arr;

/**
 * Same as the web form, except the API takes the tax rate as integer basis points.
 */
class QuoteRequest extends WebQuoteRequest
{
    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            ...Arr::except(parent::rules(), 'tax_rate'),
            'tax_rate_bp' => ['required', 'integer', 'between:0,10000'],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function taxRateBasisPoints(array $data): int
    {
        return (int) $data['tax_rate_bp'];
    }
}
