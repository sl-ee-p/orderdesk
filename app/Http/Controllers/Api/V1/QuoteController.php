<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\QuoteStatus;
use App\Http\Requests\Api\QuoteRequest;
use App\Http\Resources\QuoteResource;
use App\Models\Quote;
use App\Services\QuoteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class QuoteController extends ApiController
{
    public function __construct(private readonly QuoteService $quotes) {}

    /**
     * Optional filters: ?status= and ?customer_id=.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $this->documentFilters($request, QuoteStatus::class);

        $quotes = Quote::query()
            ->with('customer')
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['customer_id'] ?? null, fn ($query, $id) => $query->where('customer_id', $id))
            ->latest('id')
            ->paginate($this->perPage($request));

        return QuoteResource::collection($quotes);
    }

    public function store(QuoteRequest $request): JsonResponse
    {
        $quote = $this->quotes->create($request->toData(), $request->user());

        return (new QuoteResource($quote->load(['customer', 'items'])))->response()->setStatusCode(201);
    }

    public function show(Quote $quote): QuoteResource
    {
        return new QuoteResource($quote->load(['customer', 'items']));
    }

    public function update(QuoteRequest $request, Quote $quote): QuoteResource
    {
        $quote = $this->quotes->update($quote, $request->toData());

        return new QuoteResource($quote->load(['customer', 'items']));
    }

    public function destroy(Quote $quote): Response
    {
        $this->quotes->delete($quote);

        return response()->noContent();
    }
}
