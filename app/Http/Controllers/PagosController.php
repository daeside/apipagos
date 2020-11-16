<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use \App\Request\Paypal;
use \App\Request\Conekta;
use \App\Helpers\Utilities;

class PagosController extends Controller
{
    public function PaypalCreate()
    {
        $items = [
            [
                'Programa' => 'Dolphin Encounter',
                'Adultos' => 2,
                'Menores' => 1,
                'Fecha' => '2020-11-12',
                'Horario' => '10:00 AM',
                'ImporteConDescuento' => 700,
                'ClaveLocacion' => 'CZ',
                'ClavePrograma' => 'ENCO'
            ],
            [
                'Programa' => 'Dolphin Royal Swim',
                'Adultos' => 1,
                'Menores' => 0,
                'Fecha' => '2020-11-15',
                'Horario' => '11:00 AM',
                'ImporteConDescuento' => 800,
                'ClaveLocacion' => 'CZ',
                'ClavePrograma' => 'ROYS'
            ]
        ];
        $response = Paypal::CreatePayment('MXN', 1500, $items, 'ES');
        return $response;
    }

    public function PaypalExecute()
    {
        $response = Paypal::ExecutePayment('PAYID-L6ZLF5Q3U772394AF079422C', 'NDGA89RTZYXTU');
        return $response;
    }

    public function ConektaCreate()
    {
        $client = [
            'name' => 'Luis Ramirez',
            'phone' => '+5213353319758',
            'email' => 'hola@hola.com',
        ];
        $items = [
            [
                'Programa' => 'Dolphin Encounter',
                'Adultos' => 2,
                'Menores' => 1,
                'Fecha' => '2020-10-12',
                'Horario' => '10:00 AM',
                'ImporteConDescuento' => 700,
                'ClaveLocacion' => 'CZ',
                'ClavePrograma' => 'ENCO'
            ],
            [
                'Programa' => 'Dolphin Royal Swim',
                'Adultos' => 1,
                'Menores' => 0,
                'Fecha' => '2020-10-15',
                'Horario' => '11:00 AM',
                'ImporteConDescuento' => 800,
                'ClaveLocacion' => 'CZ',
                'ClavePrograma' => 'ROYS'
            ]
        ];
        $payment = [
            'type' => 'card',
            'name' => 'Mario perezwa',
            'number' => '4111111111111111',
            'exp_year' => '20',
            'exp_month' => '12'
        ];
        $response = Conekta::Create('MXN', 1500, $client, $items, $payment, 'ES');
        return $response;
    }
}
