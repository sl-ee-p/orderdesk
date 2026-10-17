<?php

namespace App\Http\Controllers;

use App\Http\Requests\QuoteStatusRequest;
use App\Models\Quote;
use App\Services\QuoteService;
use Illuminate\Http\RedirectResponse;

class QuoteStatusController extends Controller
{
    public function __invoke(QuoteStatusRequest $request, Quote $quote, QuoteService $quotes): RedirectResponse
    {
        $quotes->transition($quote, $request->status());

        return redirect()->route('quotes.show', $quote)->with('status', 'Quote status updated.');
    }
}
