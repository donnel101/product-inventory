<?php

namespace App\Services;

use App\Models\Product;

class ProductService
{
    public function isProductFound($id)
    {
        $product = Product::find($id);
        if(!$product){
            return response()->json(['message' => 'Product not Found'],404);
        }
        return $product;
    }
}