<?php


namespace App\Services;


use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductVariantPrice;

class VariantService
{
    public function saveProductVariant($product, $variants)
    {
//        dd($product, $variants);
        $variant_ids = array();
        foreach ($variants as $variant) {
            foreach ($variant['tags'] as $tag) {
                $newProductVariant = [
                    "variant" => $tag,
                    "variant_id" => $variant['option'],
                    "product_id" => $product
                ];

                $variant_ids[$tag] = ProductVariant::create($newProductVariant)->id;
            }
        }
        return $variant_ids;
    }

    public function saveVariantPrice($product, $variants, $prices)
    {

        foreach ($prices as $price) {
            $vars = explode('/', $price['title']);

//            dd($vars);

            $input_price['product_variant_one'] = $variants[$vars[0]];
            if (isset($vars[1]) && $vars[1]!="") {
                $input_price['product_variant_two'] = $variants[$vars[1]];
            }
            if (isset($vars[2] ) && $vars[2]!="") {
                $input_price['product_variant_three'] = $variants[$vars[2]];
            }

            $input_price['price'] = $price['price'];
            $input_price['stock'] = $price['stock'];
            $input_price['product_id'] = $product;

            // save prices
            ProductVariantPrice::create($input_price);

            // make some validation if all operations are ok
        }

        return response()->json(["status"=> "true", "message"=> "Product Created"]);
    }


}
