<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\ProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class ProductController extends ApiController
{
    public function index(Request $request): AnonymousResourceCollection
    {
        return ProductResource::collection(
            Product::query()->orderBy('name')->paginate($this->perPage($request)),
        );
    }

    public function store(ProductRequest $request): JsonResponse
    {
        $product = Product::create($request->toData());

        return (new ProductResource($product))->response()->setStatusCode(201);
    }

    public function show(Product $product): ProductResource
    {
        return new ProductResource($product);
    }

    public function update(ProductRequest $request, Product $product): ProductResource
    {
        $product->update($request->toData());

        return new ProductResource($product);
    }

    public function destroy(Product $product): Response
    {
        $product->delete();

        return response()->noContent();
    }
}
