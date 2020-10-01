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
        $uri = env('paypalUrlPlus') . '/oauth2/token';
        $byteArray = utf8_encode(env('paypalIdPlus') . ':' . env('paypalSecretPlus'));
        $request = ['grant_type' => 'client_credentials'];
        $headers = [
            'Authorization: Basic ' . base64_encode($byteArray),
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
        $uri = env('paypalUrlPlus') . '/payment-experience/web-profiles';
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
            $transactionList = [];
            $products = [];
            $name = $lan == "ES" ? "%s %s Adultos y %s Menores Fecha %s Horario %s" : "%s %s Adult and %s Child Date %s Schedule %s";
            $invoiceNumber = Paypal::GetRandomInvoiceNumber();
            $items = json_decode(json_encode($items));

            foreach ($items as $key => $value)
            {
                $obj = new stdClass();
                $obj->name = sprintf($name, $value->Programa, $value->Adultos, $value->Menores, Carbon::parse($value->Fecha)->format("Y-m-d"), $value->Horario);
                $obj->currency = $currency;
                $obj->price = strval($value->ImporteConDescuento);
                $obj->quantiy = '1';
                $obj->sku = sprintf('%s-%s', $value->ClaveLocacion, $value->ClavePrograma);

                array_push($products, $obj);
            }

            $transactionList = [
                'invoice_number' => $invoiceNumber,
                'amount' => [
                    'currency' => $currency,
                    'total' => $amount,
                    'details' => [
                        'subtotal' => $amount,
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
            return $transactionList;
        }
        catch (Exception $ex)
        {
            return null;
        }
    }

    public static function CreatePayment($currency, $amount, $items, $lan)
    {
        $json = '';
        $uri = env('paypalUrlPlus') . '/payments/payment';
        $token = Paypal::GetToken();
        $transactionId = strval(time());
        $headers = [
            'Authorization: Bearer ' . $token,
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
        $json = HTTP::Post($uri, $data, $headers);

        /*if (!string.IsNullOrEmpty(json))
        {
            var response = JsonConvert.DeserializeObject<CreatePaymentResponse>(json);
            foreach (var item in response.links)
            {
                if (item.rel == "approval_url")
                {
                    payData.payId = response.id;
                    payData.url = item.href;
                    break;
                }
            }
        }
        return payData;*/
        return $json;
    }
    /*

        public static string ExecutePayment(string paymentId, string payerId, string fakeError = null)
        {
            string json = string.Empty;
            string token = GetToken();
            string autorizationCode = string.Empty;
            List<Header> customHeaders = new List<Header>
            {
                new Header { Type = "PayPal-Mock-Response", Value = JsonConvert.SerializeObject(new { mock_application_codes = fakeError }) }
            };

            var headers = new RequestSettings
            {
                Content = new Header { Type = "Content-Type", Value = "application/json" },
                Authorization = new Header { Type = "Bearer", Value = token },
                CustomHeaders = customHeaders
            };

            if(string.IsNullOrEmpty(fakeError))
            {
                headers.CustomHeaders = null;
            }

            var data = new
            {
                payer_id = payerId
            };
            json = HTTP.Post(string.Format(GlobalSettings.paypalExecutePaymentUrlPlus, paymentId), data, headers);

            if (!string.IsNullOrEmpty(json))
            {
                try
                {
                    var response = JsonConvert.DeserializeObject<ExecutePaymentResponse>(json);

                    if(response.state == "approved")
                    {
                        if (response.transactions[0].related_resources[0].sale.state == "completed")
                        {
                            autorizationCode = response.transactions[0].related_resources[0].sale.id;
                        }
                    }
                    else
                    {
                        var responseError = JsonConvert.DeserializeObject<Dictionary<string, dynamic>>(json);
                        autorizationCode = responseError.FirstOrDefault(x => x.Value == "INSTRUMENT_DECLINED" || x.Value == "TRANSACTION_REFUSED").Value;
                    }
                }
                catch { }
            }
            return autorizationCode;
        }*/
}