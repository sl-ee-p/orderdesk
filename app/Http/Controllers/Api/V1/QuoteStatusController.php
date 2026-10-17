<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\QuoteStatusRequest;
use App\Http\Resources\QuoteResource;
use App\Models\Quote;
use App\Services\QuoteService;

class QuoteStatusController extends Controller
{
    public function __invoke(QuoteStatusRequest $request, Quote $quote, QuoteService $quotes): QuoteResource
    {
        $quote = $quotes->transition($quote, $request->status());

        return new QuoteResource($quote->load(['customer', 'items']));
    }
}
