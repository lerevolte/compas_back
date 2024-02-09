<?php

namespace Modules\Bitrix24\Http\Controllers\Api;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Http;
use Modules\Bitrix24\Entities\Config;
use \App\Models\Order;

class Bitrix24Controller extends Controller
{
    public function sync(Request $request)
    {
        return 'lox';
        die();
        $config = Config::first();
        $order_fields = Order::getFields();
        $params = $config->getParams();

        //$time = strtotime('03/10/2023');
        //$date = date('Y-m-d',$time);
        $date = date('Y-m-d');


        $response = Http::post($config->webhook.'crm.deal.fields', [])->collect();
        if(isset($response['result'])) {
            $b24_fields = $response['result'];
        };

        $response = Http::post($config->webhook.'crm.deal.list', [
            'filter' => [
                '>=DATE_CREATE' => $date
            ],
            'select' => ['*', 'UF_*']
            
        ])->collect();

        if(isset($response['result'])) {
            $deals = $response['result'];

            foreach ($deals as $deal) {
                $data = Order::where('number', $deal['ID'])->first();
                if(!$data)
                    $data = new Order;
                $response_products = Http::post($config->webhook.'crm.deal.productrows.get', [
                    'id' => $deal['ID'],
                ])->collect();
                $delivery_price = 0;
                $all_weight = 0;
                $products = array();
                if(isset($response_products['result'])) {
                    $arItems = array();
                    foreach($response_products['result'] as $product) {
                        if ($product['PRODUCT_ID'] != 113 && $product['PRODUCT_ID'] != 111 && $product['PRODUCT_ID']) {
                            $weight = 0;
                            $prod = \Modules\Products\Entities\Product::where('id_b24', $product['PRODUCT_ID'])->first();
                            if(!$prod) {
                                $prod = new \Modules\Products\Entities\Product();
                                $prod->id_b24 =  $product['PRODUCT_ID'];
                                $prod->name = $product['PRODUCT_NAME'];
                                if (isset($product['PROPERTY_134']))
                                    $prod->weight = $product['PROPERTY_134']['value'];
                                if (isset($product['PROPERTY_132']))
                                    $prod->link = $product['PROPERTY_132']['value'];
                                if(isset($product['PROPERTY_139']))
                                    $prod->barcode = $product['PROPERTY_139']['value'];
                                if(isset($product['PROPERTY_131']))
                                    $prod->website_id = $product['PROPERTY_131']['value'];

                                $prod->save();
                            }
                            $all_weight = $all_weight + $prod->weight*$product['QUANTITY'];
                            
                            if(!array_key_exists($prod['name'], $arItems))
                                $arItems[$prod['name']] = $product['QUANTITY'];
                            else
                                $arItems[$prod['name'].'.'] = $product['QUANTITY'];
                            $products[] = array(
                                'id' => $prod->id,
                                'name' => $prod['name'],
                                'price' => $product['PRICE'],
                                'count' => $product['QUANTITY'],
                                'weight' => $prod->weight,
                                'sum' => $product['PRICE']*$product['QUANTITY']
                            );
                        } else {
                            $delivery_price = $delivery_price + (float)$product['PRICE']*$product['QUANTITY'];
                        }
                        
                    }
                }
                $data->products = json_encode($products, JSON_UNESCAPED_UNICODE);
                //print_r($data->products);
                foreach($params['params'] as $b24_code => $code) {
                    $values = null;
                    if(isset($params['values'][$code])) {
                        $values = $params['values'][$code];
                    }
                    if($values && isset($values[$deal[$b24_code]])) {
                        $data->{$code} = $values[$deal[$b24_code]];
                        echo '<b>'.$code.':</b>'.$values[$deal[$b24_code]].'<br>';
                    } else {
                        $data->{$code} = $deal[$b24_code];
                        echo '<b>'.$code.':</b>'.$deal[$b24_code].'<br>';
                    }
                }
                $data->save();
                echo '<hr>';
            }
        }

        return false;
        
    }


}
