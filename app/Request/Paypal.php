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
        $uri = sprintf('%s/oauth2/token', env('paypalUrl'));
        $byteArray = utf8_encode(sprintf('%s:%s', env('paypalId'), env('paypalSecret')));
        $request = ['grant_type' => 'client_credentials'];
        $headers = [
            sprintf('Authorization: Basic %s', base64_encode($byteArray))
        ];
        $response = HTTP::Post($uri, $request, $headers, 'urlencode');

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
        $uri = sprintf('%s/payment-experience/web-profiles', env('paypalUrl'));
        $data = [
            'name' => $sessionId,
            'temporary' => true,
            'presentation' => [
                'logo_image' => 'https://example.com/logo_image/'
            ],
            'input_fields' => [
                'no_shipping' => 1,
                'address_override' => 1
            ],
            'flow_config' => [
                'landing_page_type' => 'billing',
                'bank_txn_pending_url' => 'https://example.com/flow_config/'
            ]
        ];
        $response = HTTP::Post($uri, $data, $headers);

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
        try
        {
            $products = [];
            $name = $lan == "ES" ? "%s %s Adultos y %s Menores Fecha %s Horario %s" : "%s %s Adult and %s Child Date %s Schedule %s";
            $invoiceNumber = Paypal::GetRandomInvoiceNumber();
            $items = json_decode(json_encode($items));

            foreach ($items as $key => $value)
            {
                $obj = new stdClass();
                $obj->name = sprintf($name, $value->Programa, $value->Adultos, $value->Menores, Carbon::parse($value->Fecha)->format("m-d-Y"), $value->Horario);
                $obj->currency = $currency;
                $obj->price = strval($value->ImporteConDescuento);
                $obj->quantity = '1';
                $obj->sku = sprintf('%s-%s', $value->ClaveLocacion, $value->ClavePrograma);

                array_push($products, $obj);
            }

            $transactionList = [
                'invoice_number' => $invoiceNumber,
                'amount' => [
                    'currency' => $currency,
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
                'payment_options' => [
                    'allowed_payment_method' => 'IMMEDIATE_PAY'
                ],
                'item_list' => [
                    'items' => $products
                ]
            ];
            return [$transactionList];
        }
        catch (Exception $ex)
        {
            return null;
        }
    }

    public static function CreatePayment($currency, $amount, $items, $lan)
    {
        $json = '';
        $uri = sprintf('%s/payments/payment', env('paypalUrl'));
        $token = Paypal::GetToken();
        $transactionId = strval(time());
        $headers = [
            sprintf('Authorization: Bearer %s', $token),
            'Content-Type: application/json'
        ];
        $webId = Paypal::GetIdWebExperience($headers, $transactionId);
        $transactions = Paypal::GetTransactionsList($currency, $amount, $items, $lan);
        $payData = [];
        $data = [
            'intent' => 'sale',
            'experience_profile_id' => $webId,
            'payer' => [
                'payment_method' => 'paypal'
            ],
            'transactions' => $transactions,
            'redirect_urls' => [
                'return_url' => 'https://www.garrafon.com/',
                'cancel_url' => 'https://www.dolphindiscovery.com/'
            ]
        ];
        $response = HTTP::Post($uri, $data, $headers);

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
        $token = Paypal::GetToken();
        $autorizationCode = '';
        $uri = sprintf('%s/payments/payment/%s/execute', env('paypalUrl'), $paymentId);

        $headers = [
            sprintf('Authorization: Bearer %s', $token),
            'Content-Type: application/json',
            sprintf('PayPal-Mock-Response: %s', json_encode(['mock_application_codes' => $fakeError ])) 
        ];

        if(empty($fakeError))
        {
            unset($headers[2]);
        }

        $data = [
            'payer_id' => $payerId
        ];
        $response = HTTP::Post($uri, $data, $headers);

        if (!empty($response))
        {
            try
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
                    $responseError = json_decode($response);
                    $autorizationCode = $responseError->name == ('INSTRUMENT_DECLINED' || 'TRANSACTION_REFUSED') ? $responseError->name : '';
                }
            }
            catch(Exception $ex) {}
        }
        return $autorizationCode;
    }
}