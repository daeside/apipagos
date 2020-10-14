<?php

namespace App\Request;

use \App\Helpers\Http;
use \App\Helpers\Utilities;
use Exception;
use stdClass;
use Carbon\Carbon;

class Paypal
{
    private static function GetToken()
    {
        $token = '';
        $byteArray = utf8_encode(sprintf('%s:%s', config('app.PaypalId'), config('app.PaypalSecret')));
        $response = HTTP::Post([
            'uri' => sprintf('%s/oauth2/token', config('app.PaypalUrl')), 
            'data' => [ 'grant_type' => 'client_credentials' ], 
            'headers' => [ sprintf('Authorization: Basic %s', base64_encode($byteArray)) ], 
            'format' => 'urlencode'
        ]);

        if(!empty($response))
        {
            $object = json_decode($response);
            $token = $object->access_token;
        }
        return $token;
    }

    private static function GetIdWebExperience($headers, $sessionId)
    {
        $webId = '';
        $data = [
            'name' => $sessionId,
            'temporary' => true,
            'presentation' => [ 'logo_image' => 'https://example.com/logo_image/' ],
            'input_fields' => [
                'no_shipping' => 1,
                'address_override' => 1
            ],
            'flow_config' => [
                'landing_page_type' => 'billing',
                'bank_txn_pending_url' => 'https://example.com/flow_config/'
            ]
        ];
        $response = HTTP::Post([
            'uri' => sprintf('%s/payment-experience/web-profiles', config('app.PaypalUrl')),
            'data' => $data,
            'headers' => $headers
        ]);

        if(!empty($response))
        {
            $object = json_decode($response);
            $webId = $object->id;
        }
        return $webId;
    }
    
    // Gets a random invoice number to be used with a sample request that requires an invoice number.
    // Returns a random invoice number in the range of 0 to 999999
    private static function GetRandomInvoiceNumber()
    {
        return strval(mt_rand(0, 999999));
    }

    private static function GetTransactionsList($currency, $amount, $items, $lan)
    {
        $products = [];
        $transactionList = [];
        $name = strtoupper($lan) == "ES" ? "%s %s Adultos y %s Menores Fecha %s Horario %s" : "%s %s Adult and %s Child Date %s Schedule %s";
        $invoiceNumber = self::GetRandomInvoiceNumber();
        $items = json_decode(json_encode($items));

        foreach ($items as $key => $value)
        {
            $product = new stdClass();
            $product->name = sprintf($name, $value->Programa, $value->Adultos, $value->Menores, Carbon::parse($value->Fecha)->format("m-d-Y"), $value->Horario);
            $product->currency = strtoupper($currency);
            $product->price = strval($value->ImporteConDescuento);
            $product->quantity = '1';
            $product->sku = sprintf('%s-%s', $value->ClaveLocacion, $value->ClavePrograma);
            array_push($products, $product);
        }

        $transaction = [
            'invoice_number' => $invoiceNumber,
            'amount' => [
                'currency' => strtoupper($currency),
                'total' => strval($amount),
                'details' => [
                    'subtotal' => strval($amount),
                    'tax' => 0,
                    'shipping' => 0,
                    'handling_fee' => 0,
                    'shipping_discount' => 0,
                    'insurance' => 0
                ]
            ],
            'description' => 'This is the payment transaction description',
            'payment_options' => [ 'allowed_payment_method' => 'IMMEDIATE_PAY' ],
            'item_list' => [ 'items' => $products ]
        ];
        $transactionList[] = $transaction;
        return $transactionList;
    }

    public static function CreatePayment($currency, $amount, $items, $lan)
    {
        $json = '';
        $payData = [];

        if(empty($items))
        {
            return $payData;
        }
        $token = self::GetToken();
        $transactionId = strval(time());
        $headers = [
            sprintf('Authorization: Bearer %s', $token),
            'Content-Type: application/json'
        ];
        $webId = self::GetIdWebExperience($headers, $transactionId);
        $transactions = self::GetTransactionsList($currency, $amount, $items, $lan);
        $data = [
            'intent' => 'sale',
            'experience_profile_id' => $webId,
            'payer' => [ 'payment_method' => 'paypal' ],
            'transactions' => $transactions,
            'redirect_urls' => [
                'return_url' => 'https://www.garrafon.com/',
                'cancel_url' => 'https://www.dolphindiscovery.com/'
            ]
        ];
        $response = HTTP::Post([
            'uri' => sprintf('%s/payments/payment', config('app.PaypalUrl')),
            'data' => $data,
            'headers' => $headers
        ]);

        if (!empty($response))
        {
            $object = json_decode($response);
            foreach ($object->links as $key => $value)
            {
                if ($value->rel == "approval_url")
                {
                    $payData = [
                        'payId' => $object->id,
                        'url' => $value->href
                    ];
                    break;
                }
            }
        }
        return $payData;
    }

    public static function ExecutePayment($paymentId, $payerId, $fakeError = null)
    {
        $json = '';
        $token = self::GetToken();
        $autorizationCode = '';

        $headers = [
            sprintf('Authorization: Bearer %s', $token),
            'Content-Type: application/json',
            sprintf('PayPal-Mock-Response: %s', json_encode(['mock_application_codes' => $fakeError ])) 
        ];

        if(empty($fakeError))
        {
            $headers[2] = null;
        }

        $response = HTTP::Post([
            'uri' => sprintf('%s/payments/payment/%s/execute', config('app.PaypalUrl'), $paymentId),
            'data' => [ 'payer_id' => $payerId ],
            'headers' => $headers,
            'errors' => true
        ]);

        if (!empty($response))
        {
            $object = json_decode($response);
            $exist = array_key_exists('state', json_decode($response, true));

            if($exist)
            {
                if($object->state == "approved")
                {
                    if ($object->transactions[0]->related_resources[0]->sale->state == "completed")
                    {
                        $autorizationCode = $object->transactions[0]->related_resources[0]->sale->id;
                    }
                }
            }
            else
            {
                $error = json_decode($response);
                $autorizationCode = $error->name == 'INSTRUMENT_DECLINED' || $error->name == 'TRANSACTION_REFUSED' ? $error->name : '';
            }
        }
        return $autorizationCode;
    }
}