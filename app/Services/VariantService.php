<?php


namespace App\Services;


use App\Models\ProductVariant;
use App\Models\ProductVariantPrice;
use App\Models\Variant;

class VariantService implements VariantServiceInterface
{
    public function saveProductVariant($product, $variants)
    {
        $variant_ids = array();
        foreach ($variants as $variant) {
            foreach ($variant['tags'] as $tag) {
                $newProductVariant = [
                    "variant_name" => $tag,
                    "variant_id" => $variant['option'],
                    "product_id" => $product
                ];

                $variant_ids[$tag] = ProductVariant::firstOrCreate($newProductVariant)->id;
            }
        }
        return $variant_ids;
    }

    /**
     *
     * @param $product : product_id
     * @param $variants : product variants
     * @param $prices :
     * @return \Illuminate\Http\JsonResponse
     */
    public function saveVariantPrice($product, $variants, $prices)
    {

        foreach ($prices as $price) {
            $vars = explode('/', $price['title']);

            $one = $variants[$vars[0]];
            if (isset($vars[1]) && $vars[1] != "") {
                $two = $variants[$vars[1]];
            }
            if (isset($vars[2]) && $vars[2] != "") {
                $three = $variants[$vars[2]];
            }

            $i_price = $price['price'];
            $stock = $price['stock'];
            $product_id = $product;

            // save prices
            ProductVariantPrice::updateOrCreate(
                [
                    'product_variant_one' => ($one ?? null),
                    'product_variant_two' => ($two ?? null),
                    'product_variant_three' => ($three ?? null),
                    'product_id' => $product_id
                ],
                ['price' => $i_price, 'stock' => $stock]
            );

            // make some validation if all operations are ok
        }

        return response()->json(["status" => "true", "message" => "Product Created"]);
    }

    public function getAllVariants()
    {
        return Variant::all();
    }

    public function getAllVariantsAsJson()
    {
        $AllMainVariants = $this->getAllVariants();

        $variants = array();
        foreach ($AllMainVariants as $x) {
            $y['id'] = $x->id;
            $y['title'] = $x->title;
            array_push($variants, $y);
        }

        return json_encode($variants);
    }

    /**
     * Update Product variant, delete and create
     *
     * returns current list of tags ids
     * @param $product_id
     * @param $pv : current product variants
     * @return array
     */
    public function updateProductVariant($product_id, $pv)
    {
        // get old pro variants
        $product_variants = ProductVariant::where("product_id", $product_id)
            ->select('id')
            ->get();

        // make array of old product variants id
        $old_product_variants = array();
        foreach ($product_variants as $opv) {
            array_push($old_product_variants, $opv['id']);
        }

        // create or get id for product variants
        $product_variant_ids = $this->saveProductVariant($product_id, $pv);

        // which pro variant needs to be deleted
        $diff = collect($old_product_variants)->diff($product_variant_ids)->all();
        // delete
        ProductVariant::whereIn('id', array_values($diff))->delete();

        // return if all ok or not
        return $product_variant_ids;
    }

}
