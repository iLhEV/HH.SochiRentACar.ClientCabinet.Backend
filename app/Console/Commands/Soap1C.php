<?php

namespace App\Console\Commands;
use GuzzleHttp\Client;

class Soap1C
{
    private $httpClient = null;

    private static $links = Array(
      'sochi' => 'http://185.50.24.156/sochi/ws/Web_Catalogs',
      'spb' => 'http://185.50.24.156/spb/ws/Web_Catalogs',
      'msk' => 'http://185.50.24.156/spb/ws/Web_Catalogs',
      'vldk' => 'http://185.50.24.156/vld/ws/Web_Catalogs',
      'crimea' => 'http://185.50.24.156/krm/ws/Web_Catalogs',
      'chlb' => "http://185.50.24.156/chl/ws/Web_Catalogs",
      'ekt' => "http://185.50.24.156/ekt/ws/Web_Catalogs",
      'krd' => "http://185.50.24.156/krd/ws/Web_Catalogs",
    );

    public function __construct()
    {
        $this->httpClient = new Client();
    }
    //Get one soap object
    public function getItems($soap_query, $link, $answer_field)
    {
        //Rquest SOAP
        $result = $this->httpClient->request('POST', $link, [
            'body' => $this->getBody($soap_query),
            'headers' => $this->getHeaders()
        ]);
        //Format soap output
        $response = strtr($result->getBody()->getContents(), ['</soap:' => '</', '<soap:' => '<']);
        $response = strtr($response, ['</m:' => '</', '<m:' => '<']);
        $output = json_decode(json_encode(simplexml_load_string($response)));
        $data = json_decode($output->Body->{$answer_field}->return);
        //Return result
        if (!$data->status) return null;
        return $data;
    }
    
    private function getHeaders()
    {
        return [
            'cache-control' => 'no-cache',
            'Connection' => 'keep-alive',
            'Content-Length' => '212',
            'Accept-Encoding' => 'gzip, deflate',
            'Host' => 'terminal.scloud.ru',
            'Postman-Token' => '08edb4de-ef95-49a4-96dc-883fea521204,290f5c2d-61a6-455e-9a87-1e514cbb2634',
            'Cache-Control' => 'no-cache',
            'Accept' => '*/*',
            'User-Agent' => 'PostmanRuntime/7.15.2',
            'Content-Type' => 'text/plain',
            'Authorization' => 'Basic '.base64_encode('dop92292:Z9kB04dw6D'),
        ];
    }

    private function getBody($content)
    {
        return '<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://www.w3.org/2003/05/soap-envelope" xmlns:ns0="127.0.0.1"><SOAP-ENV:Header/><SOAP-ENV:Body>' . $content . '</SOAP-ENV:Body></SOAP-ENV:Envelope>';
    }

    public static function getLinks()
    {
      return self::$links;
    }
    public static function getLink($city)
    {
      return self::$links[$city];
    }
}