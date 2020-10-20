<?php

namespace App\Helpers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class Utilities
{
    public static function ArrayToObject(Array $data)
    {
        $json = json_encode($data);
        $json = str_replace("[", "", $json);
        $json = str_replace("]", "", $json);
        return json_decode($json);
    }

    public static function ValidateRequest(Request $request, Array $rules)
    {
        $valid = false;
        $messages = '';
        $validator = Validator::make($request->all(), $rules);
        $errors = $validator->errors();

        if(!$validator->fails())
        {
            $valid = true;
        }
        else
        {
            foreach ($errors->all() as $message) 
            {
                $messages .= "$message ";
            }
        }
        return json_decode(json_encode([
            'valid' => $valid,
            'message' => $messages
        ]));
    }
}