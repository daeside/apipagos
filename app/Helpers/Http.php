<?php

namespace App\Helpers;

use Exception;

class Http
{
    public static function Get(Array $settings)
    {
        $settings['type'] = 'GET';
        $response = self::Request($settings);
        return $response;
    }

    public static function Post(Array $settings)
    {
        $settings['type'] = 'POST';
        $response = self::Request($settings);
        return $response;
    }
	
	public static function Patch(Array $settings)
    {
        $settings['type'] = 'PATCH';
        $response = self::Request($settings);
        return $response;
    }

    public static function Put(Array $settings)
    {
        $settings['type'] = 'PUT';
        $response = self::Request($settings);
        return $response;
    }

    public static function Delete(Array $settings)
    {
        $settings['type'] = 'DELETE';
		$response = self::Request($settings);
        return $response;
    }

    private static function Request(Array $settings)
    {
        $response = null;

        try
        {
            $client = curl_init();
            $settings = self::ValidateSettings($settings);
            $client = self::SetRequestSettings($client, $settings);
            $response = curl_exec($client);
            $code = curl_getinfo($client, CURLINFO_HTTP_CODE);
            curl_close($client);
            $response = self::Ok($settings->errors, $code) ? $response : null;
        }
        catch(Exception $ex)
        {}
        return $response;
    }

    private static function Ok($errors, $code)
    {
        return $errors ? true : $code >= 200 && $code <= 299;
    }

    private static function ValidateSettings(Array $settings)
    {
        $settings['uri'] = array_key_exists('uri', $settings) ? $settings['uri'] : '';
        $settings['data'] = array_key_exists('data', $settings) ? $settings['data'] : [];
        $settings['headers'] = array_key_exists('headers', $settings) ? $settings['headers'] : [];
        $settings['format'] = array_key_exists('format', $settings) ? $settings['format'] : '';
        $settings['errors'] = array_key_exists('errors', $settings) ? $settings['errors'] : false;
        return json_decode(json_encode($settings));
    }

    private static function SetRequestSettings($client, Object $settings)
    {
        curl_setopt($client, CURLOPT_URL, $settings->uri);
        curl_setopt($client, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($client, CURLOPT_SSLVERSION, 6); // TLS v1.2

        if (!empty($settings->headers))
        {
            curl_setopt($client, CURLOPT_HTTPHEADER, $settings->headers);
        }

        if (!empty($settings->data))
        {
            $content = self::GenerateContent($settings->data, $settings->format);
            $client = self::SetHttpMethod($client, $content, $settings->type);
        }
        return $client;
    }
	
	private static function GenerateContent($data, $type)
	{
		$content = null;
		$request = empty($type) ? '' : strtoupper($type);
		
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

    private static function SetHttpMethod($client, $data, $method)
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