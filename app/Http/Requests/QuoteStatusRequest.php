<?php

namespace App\Http\Requests;

use App\Enums\QuoteStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class QuoteStatusRequest extends FormRequest
{
    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return ['status' => ['required', Rule::enum(QuoteStatus::class)]];
    }

    public function status(): QuoteStatus
    {
        return QuoteStatus::from($this->validated('status'));
    }
}
