<?php

namespace App\Request;

use Exception;
use stdClass;
use Carbon\Carbon;
use Conekta\Conekta as ConektaLib;
use Conekta\Order;

class Conekta
{
    private static function GenerateItemList($items, $lan)
    {
        $products = [];
        $transactionList = [];
        $name = strtoupper($lan) == "ES" ? "%s %s Adultos y %s Menores Fecha %s Horario %s" : "%s %s Adult and %s Child Date %s Schedule %s";
        $items = json_decode(json_encode($items));

        foreach ($items as $key => $value)
        {
            $product = new stdClass();
            $product->name = sprintf($name, $value->Programa, $value->Adultos, $value->Menores, Carbon::parse($value->Fecha)->format("m-d-Y"), $value->Horario);
            $product->unit_price = strval($value->ImporteConDescuento);
            $product->quantity = '1';
            $product->sku = sprintf('%s-%s', $value->ClaveLocacion, $value->ClavePrograma);
            array_push($products, $product);
        }
        return $products;
    }
    
    public static function Create($currency, $amount, $client, $items, $payment, $lan)
    {
        ConektaLib::setApiKey(config('app.ConektaKey'));
        $order = '';
        $products = self::GenerateItemList($items, $lan);
        $valid_order =
        [
            'line_items'=> $products,
            'charges' => [
                [
                    'payment_method' => $payment,
                    'amount' => $amount,
                ]
            ],
            'currency' => $currency,
            'customer_info' => $client
        ];

        try 
        {
            $order = Order::create($valid_order);
        } 
        catch (Exception $ex){}
        return $order;
    }
}