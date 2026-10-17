<?php

namespace App\Http\Controllers;

use App\Models\Quote;
use App\Services\QuoteConversionService;
use Illuminate\Http\RedirectResponse;

class QuoteConversionController extends Controller
{
    public function __invoke(Quote $quote, QuoteConversionService $conversion): RedirectResponse
    {
        $order = $conversion->convert($quote);

        return redirect()->route('orders.show', $order)->with('status', 'Quote converted to an order.');
    }
}
