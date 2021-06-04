<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Variant;
use App\Services\ProductService;
use App\Services\VariantService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use function response;

class ProductController extends Controller
{
    private $productService;
    private $variantService;

    public function __construct(ProductService $productService, VariantService $variantService)
    {
        $this->productService = $productService;
        $this->variantService = $variantService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Http\Response|\Illuminate\View\View
     */
    public function index()
    {
        $products = $this->productService->productDetailsToShow();
        return view('products.index', compact('products'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Http\Response|\Illuminate\View\View
     */
    public function create()
    {
        $variants = Variant::all();
        return view('products.create', compact('variants'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $request->validate([
            "title" => 'required',
            "sku" => 'required|unique:products'
        ]);
        $product = [
            "title" => $request->title,
            "sku" => $request->sku,
            "description" => $request->description
        ];

        try {
            // operations in transactions
            DB::beginTransaction();

            // save product
            $product_id = $this->productService->saveProduct($product);

            // save images
            $product_image = $request->product_image;
            $this->productService->storeProductImage($product_id, $product_image);

            // save product variants- from variants id,
            $product_variant_ids = $this->variantService->saveProductVariant($product_id, $request->product_variant);

            // save product variant price
            $this->variantService
                ->saveVariantPrice($product_id, $product_variant_ids, $request->product_variant_prices);

            DB::commit();

            return response()->json("Product Created");

        }catch(Exception $e){
            DB::rollBack();

            return response() ->json("DB Transaction Failed..");
        }
    }


    /**
     * Display the specified resource.
     *
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function show($product)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\Models\Product $product
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Http\Response|\Illuminate\View\View
     */
    public function edit(Product $product)
    {
        $product->productVariantPrice;

        $product_variant_names = $this->productService->getProductVariantsForId($product->id);

        $product_arr = $product->toArray();

        foreach ($product_arr['product_variant_price'] as $pvp_id => $pvp) {
            $product_arr['product_variant_price'][$pvp_id] = $this->productService->pvpMaker($pvp, $product_variant_names);
        }

        $variants = $this->variantService->getAllVariantsAsJson(); // all variants for select list
        $product_arr['product_variant'] = $this->productService->optionTags($product_variant_names);

        $product = json_encode($product_arr);
        return view('products.edit', compact('variants', 'product'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request)
    {
        // update product table
        $product_updated["id"] = $request->id;
        $product_updated["title"] = $request->title;
        $product_updated["sku"] = $request->sku;
        $product_updated["description"] = $request->description;

        try {
            // operations in transactions
            DB::beginTransaction();

            // update product
            $this->productService->updateProductDetails($product_updated);

            // update product variant
            $product_variants_ids = $this->variantService->updateProductVariant($request->id, $request->product_variant);

            // update product variant price
            $this->variantService->saveVariantPrice($request->id, $product_variants_ids, $request->product_variant_prices);

            DB::commit();

            return response() ->json("Product Updated");
        }catch(Exception $e){
            DB::rollBack();
            return response() ->json("DB Transaction Failed..");
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function destroy(Product $product)
    {
        //
    }

    public function uploadImage(Request $request)
    {
        $images = array();

        // may be simple validation on request object, or
        $allowedMimeTypes = ['image/jpeg','image/gif','image/png'];
        $contentType = $request->file->getClientMimeType();

        if(! in_array($contentType, $allowedMimeTypes) ){
            return response()->json('error: Not an image');
        }else{
            $images =  $this->productService->storeTempImage($request->file);
        }

        return $images;
    }
}
