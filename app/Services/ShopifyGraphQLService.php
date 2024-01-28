<?php
// app/ShopifyGraphQLService.php

namespace App\Services;

use App\Models\newSystemColourSize;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class ShopifyGraphQLService
{
  protected $client;
  protected $accessToken;
  protected $shopifyUrl;

  public function __construct()
  {
    $this->client = new Client();
    $this->accessToken = config('services.shopify.access_token');
    $this->shopifyUrl = config('services.shopify.url');
  }

  public function getAllProducts()
  {
    $query = <<<'GRAPHQL'
        {
          products(first: 10) {
            edges {
              node {
                id
                title
                description
              }
            }
          }
        }
        GRAPHQL;

    return $this->request($query);
  }

  public function createProduct($product)
  {
    try {
      $variants = $this->buildVariants($product->ciStockID, $product->weight, $product->shopifyWebSKU);
      $src = "https://dfstudio-d420.kxcdn.com/wordpress/wp-content/uploads/2019/06/digital_camera_photo-980x653.jpg";

      //dd($variants);
      $query = <<<GRAPHQL
      mutation {
        productCreate(input:{
          title:"{$product->newSystemShortDescription}",
          descriptionHtml:"{$product->newSystemLongDescription}",
          vendor:"{$product->newSystemUDF1}",
          handle:"{$product->ciStockCode}",
          productType:"{$product->newSystemUDF2}",
          published:true,
          options: [{$this->buildOptions($product->ciStockID)}],
          status:ACTIVE,
          tags:"{$product->newSystemShortDescription}",
          variants:[{$variants}],
        }, media:[{
              alt:"demo",
              mediaContentType:IMAGE,
              originalSource:"{$src}"
        }]) {
          product {
           id
           title
          }
          userErrors {
            field
            message
          }
        }
      }
      GRAPHQL;

      print_r($query);
      return $this->request($query);
    } catch (Exception $e) {
      Log::error('Exception while creating product in Shopify GraphQL: ' . $e->getMessage());
      return null;
    }
  }
  protected function buildOptions($ciStockCode)
  {
    $options = [];
    $ColourSizes = newSystemColourSize::where('ciStockID', $ciStockCode)->first();

    if ($ColourSizes->ciColourID != 0) {
      $options[] = '"Color"';
    }

    if ($ColourSizes->ciSizeID != 0) {
      $options[] = '"Size"';
    }

    return implode(',', $options);
  }


  private function buildVariants($ciStockCode, $weight, $shopifyWebSKU)
  {
    $ColourSizes = newSystemColourSize::where('ciStockID', $ciStockCode)->get();

    $variants = [];
    $str = [];
    $uniqueOptions = [];

    foreach ($ColourSizes as $variant) {
      $sku = $variant->newSystemSKU;

      if ($variant->ciColourID == 0 && $variant->ciSizeID == 0) {
        $sku = $shopifyWebSKU;
      }
      if ($variant->price5 == 0) {
        $price = $variant->price1 ?? 0;
        $ComPrice = $variant->price5 ?? 0;
      } else {
        $price = $variant->price5 ?? 0;
        $ComPrice = $variant->price1 ?? 0;
      }
      $quantity = $variant->stock_in_hand ?? 0;
      $options = [];

      if ($variant->ciColourID != 0) {
        $options[] = '"' . $variant->ciColourID . '"';
      }

      if ($variant->ciSizeID != 0) {
        $options[] = '"' . $variant->ciSizeID . '"';
      }
      $outputArray = collect($options)->map(function ($item) {
        return strval($item);
      })->toArray();

      $src = "https://dfstudio-d420.kxcdn.com/wordpress/wp-content/uploads/2019/06/digital_camera_photo-980x653.jpg";
      $a = implode(',', $outputArray);

      if (!in_array($a, $uniqueOptions)) {
        $str = '{';
        $str .= 'sku: "' . $sku . '",';
        $str .= 'price: "' . $price . '",';
        $str .= 'compareAtPrice: "' . $ComPrice . '",';
        $str .= 'inventoryPolicy: DENY,';
        $str .= 'weight:' . $weight . ',';
        $str .= 'barcode:"' . $variant->barcode . '",';
        $str .= 'inventoryQuantities: [';
        $str .= '{';
        $str .= '  availableQuantity:' . $quantity . ',';
        $str .= '  locationId: "gid://shopify/Location/63262490688"';
        $str .= '}';
        $str .= '],';
        $str .= 'mediaSrc:"' . $src . '",';

        if ($a) {
          $str .= 'options:[' . $a . ']';
        }
        $str .= '}';

        $variants[] = $str;
        $uniqueOptions[] = $a; // Add options to the list of unique options

      }
    }

    return (implode(',', $variants));
    return json_encode($str);
  }

  protected function request($query)
  {
    $response = $this->client->post($this->shopifyUrl, [
      'headers' => [
        'Content-Type' => 'application/json',
        'X-Shopify-Access-Token' => $this->accessToken,
      ],
      'json' => ['query' => $query],
    ]);

    return json_decode($response->getBody()->getContents(), true);
  }
}
