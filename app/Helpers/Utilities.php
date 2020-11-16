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

    public static function Guid()
    {
        // Create a token
        $token = sprintf('%s%s%s', 
            $_SERVER['HTTP_HOST'], 
            $_SERVER['REQUEST_URI'], 
            uniqid(rand(), true)
        );
    
        // GUID is 128-bit hex
        $hash = strtoupper(md5($token));

        // Create formatted GUID. GUID format is XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX for readability
        $guid = sprintf('%s-%s-%s-%s-%s', 
            substr($hash,  0,  8), 
            substr($hash,  8,  4), 
            substr($hash, 12,  4), 
            substr($hash, 16,  4), 
            substr($hash, 20, 12)
        );
            
        return $guid;
    }
}