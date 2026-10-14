<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Services\ProductStore;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __construct(private readonly ProductStore $products)
    {
    }

    public function index(): View
    {
        return view('products.index');
    }

    /**
     * Rows ordered by submitted datetime, each with its total value, plus the grand total.
     */
    public function list(): JsonResponse
    {
        return response()->json($this->payload());
    }

    public function store(ProductRequest $request): JsonResponse
    {
        $product = $this->products->create($request->validated());

        return response()->json([
            'message' => 'Product saved.',
            'product' => $this->withTotal($product),
            ...$this->payload(),
        ], 201);
    }

    public function update(ProductRequest $request, string $id): JsonResponse
    {
        $product = $this->products->update($id, $request->validated());

        abort_if($product === null, 404, 'Product not found.');

        return response()->json([
            'message' => 'Product updated.',
            'product' => $this->withTotal($product),
            ...$this->payload(),
        ]);
    }

    private function payload(): array
    {
        $rows = array_map(fn (array $product) => $this->withTotal($product), $this->products->all());

        return [
            'products' => $rows,
            'grand_total' => round(array_sum(array_column($rows, 'total_value')), 2),
        ];
    }

    private function withTotal(array $product): array
    {
        return [
            ...$product,
            'total_value' => round($product['quantity'] * $product['price'], 2),
        ];
    }
}
