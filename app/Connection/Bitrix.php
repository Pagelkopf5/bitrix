<?php

namespace App\Connection;

class Bitrix
{
    public static function ConnectionBitrix($query, $queryUrl, $ssl_verify)
    {
        $queryUrl1 = env('BITRIX24_WEBHOOK_URL') . $queryUrl;

        $options = [
            'http' => [
                'header'  => "Content-type: application/x-www-form-urlencoded",
                'method'  => 'POST',
                'content' => $query,
                'ignore_errors' => true,
            ],
            'ssl' => [
                'verify_peer' => $ssl_verify,
                'verify_peer_name' => $ssl_verify,
            ],
        ];

        $context = stream_context_create($options);
        $result = file_get_contents($queryUrl1, false, $context);

        return $result;
    }
}