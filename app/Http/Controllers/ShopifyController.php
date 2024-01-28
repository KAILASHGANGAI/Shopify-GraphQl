<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\ShopifyGraphQLService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ShopifyController extends Controller
{

    private $shopifyGraphQLService;

    public function __construct(ShopifyGraphQLService $shopifyGraphQLService)
    {
        $this->shopifyGraphQLService = $shopifyGraphQLService;
    }
    public function index()
    {
        return  $response = $this->shopifyGraphQLService->getAllProducts();
    }
    public function createProduct(Request $request)
    {
        try {
            $products = Product::where('hasVariation', 2)->where('ciStockID', 11416)->first();
            if (!$products) throw new Exception("No product found with hasVariation=2");
            $response = $this->shopifyGraphQLService->createProduct($products);
            dd($response);
            if ($response['data']['productCreate']['product']['id']) {
                echo "Product created successfully" . "<br/>";
                echo "CiStockCode = " . $products->ciStockCode . "<br/>";
                $pid = $response['data']['productCreate']['product']['id'];
                // $Variantresponse = $this->shopifyGraphQLService->buildVariants($products->ciStockID, $products->weight, $products->shopifyWebSKU, $pid);
                // dd($Variantresponse);
            } else {
                echo "Product failed to Create" . "<br/>";
                echo "CiStockCode = " . $products->ciStockCode . "<br/>";
            }
        } catch (Exception $e) {
            Log::error('Exception while creating products in Shopify GraphQL: ' . $e->getMessage());
            return $e->getMessage();
        }
    }
}
