<?php

namespace App\Helpers;

use \App\Helpers\Http;
use \App\Helpers\Utilities;
use Exception;
use stdClass;
use Carbon\Carbon;

class PaypalPlus
{
    public static function GetToken()
    {
        $token = '';
        $uri = env('paypalUrlPlus') . '/oauth2/token';
        $byteArray = utf8_encode(env('paypalIdPlus') . ':' . env('paypalSecretPlus'));
        $request = ['grant_type' => 'client_credentials'];
        $headers = [
            'Authorization: Basic ' . base64_encode($byteArray),
        ];
        $response = HTTP::Post($uri, $request, $headers, 'urlencode');

        try
        {
            $object = json_decode($response);
            $token = $object->access_token;
        }
        catch(Exception $ex){}
        return $token;
    }

    public static function GetIdWebExperience($headers, $sessionId)
    {
        $webId = '';
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
        $response = HTTP::Post(env('paypalUrlPlus') . '/payment-experience/web-profiles', $data, $headers);

        try
        {
            $object = json_decode($response);
            $webId = $object->id;
        }
        catch(Exception $ex){}
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
            $invoiceNumber = PaypalPlus::GetRandomInvoiceNumber();

            foreach ($item as $key => $value)
            {
                $obj = new stdClass();
                $obj->name = sprintf($name, $item->Programa, $item->Adultos, $item->Menores, Carbon::parse($item->Fecha)->format("Y-m-d"), $item->Horario);
                $obj->currency = $currency;
                $obj->price = strval($item->ImporteConDescuento);
                $obj->quantiy = '1';
                $obj->sku = sprintf('%s-%s', $item->ClaveLocacion, $item->ClavePrograma);

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

        /*
        // Generamos la referencia externa con base en el Unix Time
        private static string ConvertToUnixTime()
        {
            DateTime sTime = new DateTime(1970, 1, 1, 0, 0, 0, 0, DateTimeKind.Utc);
            return ((long)(DateTime.Now - sTime).TotalSeconds).ToString();
        }

        public static CreatePaymentResponseData CreatePayment(string currency, string amount, List<CartItem> items, string lan)
        {
            string json = string.Empty;
            string token = GetToken();
            string transactionId = ConvertToUnixTime();
            var headers = new RequestSettings
            {
                Content = new Header { Type = "Content-Type", Value = "application/json" },
                Authorization = new Header { Type = "Bearer", Value = token }
            };
             string WebId = GetIdWebExperience(headers, transactionId);
            var payData = new CreatePaymentResponseData();
            var data = new
            {
                intent = "sale",
                experience_profile_id = WebId,
                payer = new {
                    payment_method = "paypal"
                },
                transactions = GetTransactionsList(currency, amount, items, lan),
                redirect_urls = new
                {
                    return_url = "https://www.garrafon.com/",
                    cancel_url = "https://www.dolphindiscovery.com/"
                }
            };
            json = HTTP.Post(GlobalSettings.paypalPaymentUrlPlus, data, headers);

            if (!string.IsNullOrEmpty(json))
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
            return payData;
        }

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