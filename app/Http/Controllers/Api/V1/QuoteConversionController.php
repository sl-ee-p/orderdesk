<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Quote;
use App\Services\QuoteConversionService;
use Illuminate\Http\JsonResponse;

class QuoteConversionController extends Controller
{
    public function __invoke(Quote $quote, QuoteConversionService $conversion): JsonResponse
    {
        $order = $conversion->convert($quote);

        return (new OrderResource($order->load(['customer', 'items'])))->response()->setStatusCode(201);
    }
}
