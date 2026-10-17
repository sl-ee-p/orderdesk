<?php

namespace App\Http\Controllers;

use App\Enums\QuoteStatus;
use App\Models\Quote;
use App\Services\QuoteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class QuoteStatusController extends Controller
{
    public function __invoke(Request $request, Quote $quote, QuoteService $quotes): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::enum(QuoteStatus::class)],
        ]);

        $quotes->transition($quote, QuoteStatus::from($data['status']));

        return redirect()->route('quotes.show', $quote)->with('status', 'Quote status updated.');
    }
}
