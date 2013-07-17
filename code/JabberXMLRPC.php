<?php

class JabberXMLRPC
{
    private static function makeCall($method, $params = array())
    {
        $request = xmlrpc_encode_request($method, $params);
        $ch = curl_init();

        $headers = array(
            "Content-Type: text/xml",
            "User-Agent: SilverStripe"
        );

        curl_setopt($ch, CURLOPT_URL, sprintf("%s/RPC2", JabberConfig::$XMLRPCHost));
        curl_setopt($ch, CURLOPT_PORT, JabberConfig::$XMLRPCPort);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $request);

        $response = xmlrpc_decode_request(curl_exec($ch), $method);
        curl_close($ch);


        if($response) {
            $response = new ArrayObject($response);
            $response->setFlags(ArrayObject::ARRAY_AS_PROPS);
        }
        return $response;
    }

    public static function __callStatic($method, $arguments)
    {
        return self::makeCall($method, $arguments);
    }
}
