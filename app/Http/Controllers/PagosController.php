<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use \App\Helpers\PaypalPlus;

class PagosController extends Controller
{
    public function Test()
    {
        $token = PaypalPlus::GetToken();
        $headers = [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json'
        ];
        return PaypalPlus::GetIdWebExperience($headers, session_create_id());
    }
}
