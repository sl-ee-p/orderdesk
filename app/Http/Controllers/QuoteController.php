<?php

namespace App\Http\Controllers;

use App\Http\Requests\QuoteRequest;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Quote;
use App\Services\QuoteService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;

class QuoteController extends Controller
{
    public function __construct(private readonly QuoteService $quotes) {}

    public function index(): View
    {
        return view('quotes.index', [
            'quotes' => Quote::query()->with('customer')->latest('id')->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('quotes.create', $this->formData(new Quote(['tax_rate_bp' => 2200])));
    }

    public function store(QuoteRequest $request): RedirectResponse
    {
        $quote = $this->quotes->create($request->toData(), $request->user());

        return redirect()->route('quotes.show', $quote)->with('status', 'Quote created.');
    }

    public function show(Quote $quote): View
    {
        $quote->load(['customer', 'items', 'order', 'user']);

        return view('quotes.show', ['quote' => $quote]);
    }

    public function edit(Quote $quote): View|RedirectResponse
    {
        if (! $quote->status->isEditable()) {
            return redirect()->route('quotes.show', $quote)->with('error', 'Only draft quotes can be edited.');
        }

        $quote->load('items');

        return view('quotes.edit', $this->formData($quote));
    }

    public function update(QuoteRequest $request, Quote $quote): RedirectResponse
    {
        $this->quotes->update($quote, $request->toData());

        return redirect()->route('quotes.show', $quote)->with('status', 'Quote updated.');
    }

    public function destroy(Quote $quote): RedirectResponse
    {
        $this->quotes->delete($quote);

        return redirect()->route('quotes.index')->with('status', 'Quote deleted.');
    }

    /**
     * @return array{quote: Quote, customers: Collection<int, Customer>, products: Collection<int, Product>}
     */
    private function formData(Quote $quote): array
    {
        return [
            'quote' => $quote,
            'customers' => Customer::query()->orderBy('name')->get(['id', 'name', 'company']),
            'products' => Product::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'unit_price_cents']),
        ];
    }
}
