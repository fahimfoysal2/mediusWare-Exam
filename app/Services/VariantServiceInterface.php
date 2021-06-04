<?php

namespace App\Services;

interface VariantServiceInterface
{
    public function saveProductVariant($product, $variants);

    /**
     *
     * @param $product : product_id
     * @param $variants : product variants
     * @param $prices :
     * @return \Illuminate\Http\JsonResponse
     */
    public function saveVariantPrice($product, $variants, $prices);

    public function getAllVariants();

    public function getAllVariantsAsJson();

    /**
     * Update Product variant, delete and create
     *
     * returns current list of tags ids
     * @param $product_id
     * @param $pv : current product variants
     * @return array
     */
    public function updateProductVariant($product_id, $pv);
}
