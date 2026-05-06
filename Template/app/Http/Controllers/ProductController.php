<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Product\Actions\CreateProductAction;
use App\Application\Product\Actions\DeleteProductAction;
use App\Application\Product\Actions\UpdateProductAction;
use App\Application\Product\CreateProductData;
use App\Application\Product\ProductFilter;
use App\Application\Product\Queries\GetProductByIdQuery;
use App\Application\Product\Queries\GetProductsQuery;
use App\Application\Product\UpdateProductData;
use App\Domain\Product\ProductId;
use App\Http\Requests\CreateProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class ProductController extends Controller
{
    public function index(Request $request, GetProductsQuery $query): AnonymousResourceCollection
    {
        $products = $query->handle(ProductFilter::fromRequest($request));

        return ProductResource::collection($products);
    }

    public function show(string $id, GetProductByIdQuery $query): ProductResource|JsonResponse
    {
        $product = $query->handle(ProductId::fromString($id));

        if ($product === null) {
            return response()->json(['message' => 'Product not found.'], 404);
        }

        return new ProductResource($product);
    }

    public function store(CreateProductRequest $request, CreateProductAction $action): JsonResponse
    {
        $productId = $action->handle(CreateProductData::fromRequest($request));

        return response()->json(['id' => (string) $productId], 201);
    }

    public function update(string $id, UpdateProductRequest $request, UpdateProductAction $action): JsonResponse
    {
        $action->handle(ProductId::fromString($id), UpdateProductData::fromRequest($request));

        return response()->json(null, 204);
    }

    public function destroy(string $id, DeleteProductAction $action): JsonResponse
    {
        $action->handle(ProductId::fromString($id));

        return response()->json(null, 204);
    }
}
