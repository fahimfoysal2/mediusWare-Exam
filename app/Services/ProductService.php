<?php


namespace App\Services;


use App\Models\Product;

class ProductService
{
    public function saveProduct($product)
    {
        return Product::create($product)->id;
    }
}
