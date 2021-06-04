<?php


namespace App\Services;


use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Support\Facades\DB;
use Intervention\Image\Facades\Image;

class ProductService implements ProductServiceInterface
{
    private $tempImagePath;
    private $productImagePath;

    public function __construct()
    {
        if (!isset($this->tempImagePath)) {
            $this->tempImagePath = public_path('images/temp');
        }

        if (!isset($this->productImagePath)) {
            $this->productImagePath = public_path('images/products');
        }
    }

    public function saveProduct($product)
    {
        return Product::create($product)->id;
    }

    /**
     * Make List of products to show in product listing
     *
     * @return array
     */
    public function productDetailsToShow()
    {
        $raw_products = Product::paginate(10);

        // store id for products in current page
        $current_products_ids = array();

        foreach ($raw_products as $products) {
            $products->productVariantPrice;

            array_push($current_products_ids, $products->id);
        }

        // get variant names to make title from variant id array
        $variant_names = $this->getProductVariantsForIdArray($current_products_ids);

        // make array from collection
        $structured_products = $raw_products->toArray();

        // make variant-names
        foreach ($structured_products['data'] as $data_id => $a) {
            foreach ($a['product_variant_price'] as $pvp_id => $pvp) {
                $structured_products['data'][$data_id]['product_variant_price'][$pvp_id] = $this->pvpMaker($pvp, $variant_names);
            }
        }


        return $structured_products;
    }


    public function getProductVariantsForIdArray(array $ids)
    {
        return DB::table('product_variants')
            ->whereIn('product_id', $ids)->select('id', 'variant_name')->get();

    }

    public function getProductVariantsForId(int $id)
    {
        return DB::table('product_variants')
            ->where('product_id', $id)->select('id', 'variant_name', 'variant_id')->get();
    }


    /**
     * Make Product variant-combination, Price, Stock For display
     *
     * @param $variant : array representation of Model ProductVariantPrice
     * @param $variant_names : all current variant names as collection
     * @return array
     */
    public function pvpMaker($variant, $variant_names): array
    {
        // make variant title
        $id = $variant['id'];
        $title = ($variant_names->where('id', $variant['product_variant_one'])->pluck('variant_name'))[0];
        if (!is_null($variant['product_variant_two'])) {
            $id = $variant['id'];
            $title .= "/" . ($variant_names->where('id', $variant['product_variant_two'])->pluck('variant_name'))[0];
        }
        if (!is_null($variant['product_variant_three'])) {
            $id = $variant['id'];
            $title .= "/" . ($variant_names->where('id', $variant['product_variant_three'])->pluck('variant_name'))[0];
        }

        $price = $variant['price'];
        $stock = $variant['stock'];

        // make array of product variant price
        return [
            "id" => $id,
            "title" => $title,
            "price" => $price,
            "stock" => $stock,
        ];
    }

    /**
     * used variants for a product
     * returns array of [ option, tags ]
     *
     * @param $productVariantNames
     * @return array
     */
    public function optionTags($productVariantNames)
    {
        $product_variant = ($productVariantNames->groupBy('variant_id'))->toArray();

        $z = array();
        foreach ($product_variant as $id => $pv) {
            $tmp['option'] = $id;
            foreach ($pv as $id => $pv_i) {
                $tmp['tags'][$id] = $pv_i->variant_name;
            }
            array_push($z, $tmp);
        }

        return $z;
    }

    public function updateProductDetails($product_updated)
    {
        $product = Product::find($product_updated['id']);
        // is anything changed? saving anyway
        $product->id = $product_updated["id"];
        $product->title = $product_updated["title"];
        $product->sku = $product_updated["sku"];
        $product->description = $product_updated["description"];

        $product->save();

        // return if all ok or not
    }

    /**
     * Temporary store images before product is created
     *
     * @param $image
     * @return string[]
     */
    public function storeTempImage($image)
    {

        $time = time();
        $imageName = $time . '.' . $image->getClientOriginalExtension();
        $thumbnail = $time . "_thumb." . $image->getClientOriginalExtension();

        // store thumbnail
        $img = Image::make($image->path());
        $img->resize(110, 110, function ($const) {
            $const->aspectRatio();
        })->save($this->tempImagePath . '/' . $thumbnail);

        // store main image
        $image->move($this->tempImagePath, $imageName);

        // return names of created files
        return ["image" => $imageName, "thumbnail" => $thumbnail];
    }

    /**
     * Store image for a product
     *
     * @param $product_id
     * @param $images
     */
    public function storeProductImage($product_id, $images)
    {
        $isThumb = isset($images['thumbnail']) ? true : false;

        if (count($images) > 0) {
            ProductImage::updateOrCreate(
                ['product_id' => $product_id],
                ['file_path' => $images['image'], 'thumbnail' => $isThumb]
            );
        }

        // move product images to permanent place

        if (!is_dir($this->productImagePath)) mkdir($this->productImagePath);
        rename(
            $this->tempImagePath . '/' . $images['image'],
            $this->productImagePath . '/' . $images['image']
        );
        rename(
            $this->tempImagePath . '/' . $images['thumbnail'],
            $this->productImagePath . '/' . $images['thumbnail']
        );
    }
}
