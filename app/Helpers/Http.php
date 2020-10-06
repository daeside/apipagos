<?php

namespace App\Helpers;

use Exception;

class Http
{
    public static function Get($uri, $settings = null)
    {
        $response = Http::Request($uri, $settings, 'GET');
        return $response;
    }

    public static function Post($uri, $data, $settings = null, $dataType = null)
    {
        $response = Http::Request($uri, $settings, 'POST', $data, $dataType);
        return $response;
    }
	
	public static function Patch($uri, $data, $settings = null, $dataType = null)
    {
        $response = Http::Request($uri, $settings, 'PATCH', $data, $dataType);
        return $response;
    }

    public static function Put($uri, $data, $settings = null, $dataType = null)
    {
        $response = Http::Request($uri, $settings, 'PUT', $data, $dataType);
        return $response;
    }

    public static function Delete($uri, $settings = null)
    {
		$response = Http::Request($uri, $settings, 'DELETE');
        return $response;
    }

    private static function Request($uri, $settings, $method, $data = null, $dataType = null)
    {
        $response = null;

        try
        {
            $client = curl_init();
            $client = Http::SetRequestSettings($client, $uri, $data, $method, $settings, $dataType);
            $response = curl_exec($client);
            $httpCode = curl_getinfo($client, CURLINFO_HTTP_CODE);
            curl_close($client);
            //$response = Http::Ok($httpCode) ? $response : null;
        }
        catch(Exception $ex)
        {}
        return $response;
    }

    private static function Ok($httpCode)
    {
        return $httpCode >= 200 && $httpCode <= 299;
    }

    private static function SetRequestSettings($client, $uri, $data, $method, $settings, $dataType)
    {
        curl_setopt($client, CURLOPT_URL, $uri);
        curl_setopt($client, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($client, CURLOPT_SSLVERSION, 6); // TLS v1.2

        if (!empty($settings))
        {
            curl_setopt($client, CURLOPT_HTTPHEADER, $settings);
        }
		$content = Http::GenertateContent($dataType, $data);
        $client = Http::SetHttpMethod($client, $method, $content);
        return $client;
    }
	
	private static function GenertateContent($dataType, $data)
	{
		$content = null;
		$request = empty($dataType) ? '' : strtoupper($dataType);
		
		switch ($request)
        {
            case 'JSON':
				$content = json_encode($data);
				break;
			case 'URLENCODE':
                $content = http_build_query($data);
				break;
            default:
				$content = json_encode($data);
            break;
        }
        return $content;
	}

    private static function SetHttpMethod($client, $method, $data)
    {
        switch ($method)
        {
            case 'GET':
                curl_setopt($client, CURLOPT_CUSTOMREQUEST, 'GET');
                break;
            case 'POST':
                curl_setopt($client, CURLOPT_CUSTOMREQUEST, 'POST');
                curl_setopt($client, CURLOPT_POSTFIELDS, $data);
                break;
			case 'PUT':
                curl_setopt($client, CURLOPT_CUSTOMREQUEST, 'PUT');
                curl_setopt($client, CURLOPT_POSTFIELDS, $data);
                break;
			case 'PATCH':
                curl_setopt($client, CURLOPT_CUSTOMREQUEST, 'PATCH');
                curl_setopt($client, CURLOPT_POSTFIELDS, $data);
                break;
			case 'DELETE':
                curl_setopt($client, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;
        }
        return $client;
    }
}