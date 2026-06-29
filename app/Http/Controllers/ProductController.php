<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use Illuminate\Http\Request;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{

    protected $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }


    public function index(Request $request)
    {
        // return $request;
        $query = Product::query();

        // Keyword search on name and description
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }

        // Exact match on SKU
        if ($request->has('sku')) {
            $query->where('sku', $request->sku);
        }

        // Filter by out-of-stock
        if ($request->has('out_of_stock')) {
            if ($request->out_of_stock == 1) {
                $query->where('stock', 0);
            } elseif ($request->out_of_stock == 0) {
                $query->where('stock', '>', 0);
            }
        }

        $products = $query->paginate(15);

        return ProductResource::collection($products);
    }

    public function store(StoreProductRequest $request)
    {
        $product = Product::create($request->validated());

        return new ProductResource($product);
    }

    public function show($id)
    {
        $product = $this->productService->isProductFound($id);
        if ($product instanceof JsonResponse) {
            // It's a JSON response (Product was not found), so return it immediately
            return $product; 
        }
        return new ProductResource($product);
    }

    public function update(UpdateProductRequest $request, $id)
    {
        $product = $this->productService->isProductFound($id);
        if ($product instanceof JsonResponse) {
            // It's a JSON response (Product was not found), so return it immediately
            return $product; 
        }
        $product->update($request->validated());

        return new ProductResource($product);
    }

    public function destroy($id)
    {
        $product = $this->productService->isProductFound($id);
        if ($product instanceof JsonResponse) {
            // It's a JSON response (Product was not found), so return it immediately
            return $product; 
        }
        $product->delete();

        return response()->json(['message' => 'Product deleted successfully'],200);
    }
}