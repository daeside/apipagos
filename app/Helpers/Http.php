<?php

namespace App\Helpers;

class Http
{
    public static function Get($uri, $settings = null)
    {
        $response = Http::Request($uri, $settings, 'GET');
        return $response;
    }

    public static function Post($uri, $data, $settings = null, $requestFormatData = null)
    {
        $response = Http::Request($uri, $settings, 'POST', $data, $requestFormatData);
        return $response;
    }
	
	public static function Patch($uri, $data, $settings = null, $requestFormatData = null)
    {
        $response = Http::Request($uri, $settings, 'PATCH', $data, $requestFormatData);
        return response.Result;
    }

    public static function Put($uri, $data, $settings = null, $requestFormatData = null)
    {
        $response = Http::Request($uri, $settings, 'PUT', $data, $requestFormatData);
        return $response;
    }

    public static function Delete($uri, $settings = null)
    {
		$response = Http::Request($uri, $settings, 'DELETE');
        return $response;
    }

    private static function Request($uri, $settings, $method, $data = null, $requestFormatData = null)
    {
        $response = null;

        try
        {
            $client = curl_init();
            $client = Http::SetRequestSettings($client, $uri, $data, $method, $settings, $requestFormatData);
            $response = curl_exec($client);
            curl_close($client);
        }
        catch(exception $ex)
        {}
        return $response;
    }

    private static function SetRequestSettings($client, $uri, $data, $method, $settings, $requestFormatData)
    {
        curl_setopt($client, CURLOPT_URL, $uri);
        curl_setopt($client, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($client, CURLOPT_SSLVERSION, 6); // TLS v1.2

        if (!empty($settings))
        {
            curl_setopt($client, CURLOPT_HTTPHEADER, $settings);
        }
		$content = Http::GenertateContent($requestFormatData, $data);
        $client = Http::SetHttpMethod($client, $method, $content);
        return $client;
    }
	
	private static function GenertateContent($requestFormatData, $data)
	{
		$content = null;
		$request = empty($requestFormatData) ? '' : strtoupper($requestFormatData);
		
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