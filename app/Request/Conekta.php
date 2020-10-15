<?php

namespace App\Request;

use \App\Helpers\Http;
use \App\Helpers\Utilities;
use Exception;
use stdClass;
use Carbon\Carbon;
use Conekta\Conekta as ConektaLib;

class Conekta
{
    public static function Create($currency, $amount)
    {
        ConektaLib::setApiKey(config('app.ConektaKey'));
        $valid_order =
        [
            'line_items'=> [
                [
                    'name' => 'Box of Cohiba S1s',
                    'description' => 'Imported From Mex.',
                    'unit_price' => 2000,
                    'quantity' => 1,
                    'sku' => 'cohb_s1'
                ]
            ],
            'charges' => [
                [
                    'payment_method' => [
                        'type' => 'card',
                        'name' => 'Mario perez',
                        'number' => '4111111111111111',
                        'exp_year' => '20',
                        'exp_month' => '12'
                    ],
                    'amount' => $amount,
                ]
            ],
            'currency' => $currency,
            'customer_info' => [
                'name' => 'John Constantine',
                'phone' => '+5213353319758',
                'email' => 'hola@hola.com',
            ]
        ];

        try 
        {
            $order = \Conekta\Order::create($valid_order);
            return $order;
        } 
        catch (\Conekta\ProcessingError $e)
        {
            echo $e->getMessage();
        } 
        catch (\Conekta\ParameterValidationError $e)
        {
            echo $e->getMessage();
        } 
    }

    public static function Capture($id)
    {
        ConektaLib::setApiKey(config('app.ConektaKey'));
        $order = \Conekta\Order::find($id);
        return $order->capture();
    }
}