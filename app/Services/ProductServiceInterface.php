<?php

namespace App\Services;

interface ProductServiceInterface
{
    public function saveProduct($product);

    /**
     * Make List of products to show in product listing
     *
     * @return array
     */
    public function productDetailsToShow();

    public function getProductVariantsForIdArray(array $ids);

    public function getProductVariantsForId(int $id);

    /**
     * Make Product variant-combination, Price, Stock For display
     *
     * @param $variant : array representation of Model ProductVariantPrice
     * @param $variant_names : all current variant names as collection
     * @return array
     */
    public function pvpMaker($variant, $variant_names): array;

    /**
     * used variants for a product
     * returns array of [ option, tags ]
     *
     * @param $productVariantNames
     * @return array
     */
    public function optionTags($productVariantNames);

    public function updateProductDetails($product_updated);
}
