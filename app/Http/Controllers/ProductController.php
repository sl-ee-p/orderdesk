<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class ProductController extends Controller
{
    public function index(): View
    {
        return view('products.index', [
            'products' => Product::query()->orderBy('name')->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('products.create', ['product' => new Product(['is_active' => true])]);
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        Product::create($request->toData());

        return redirect()->route('products.index')->with('status', 'Product created.');
    }

    public function edit(Product $product): View
    {
        return view('products.edit', ['product' => $product]);
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        $product->update($request->toData());

        return redirect()->route('products.index')->with('status', 'Product updated.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        // Existing quote and order lines keep their snapshot; their product link becomes null.
        $product->delete();

        return redirect()->route('products.index')->with('status', 'Product deleted.');
    }
}
