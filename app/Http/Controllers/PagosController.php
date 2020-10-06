<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use \App\Request\Paypal;

class PagosController extends Controller
{
    public function Test()
    {
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
        //$payData = Paypal::CreatePayment('MXN', 1500, $items, 'ES');
        return Paypal::ExecutePayment('PAYID-L56KISI6GS01762KA393291P', 'AQ67T33R5EU22');
    }
}
