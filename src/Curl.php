<?php

/*
        ***THIS FILE INCLUDES CODE FROM MEDIAWIKI API DEMOS THAT ARE
        LICENSED UNDER MIT LICENSE***
*/

// Classe do cURL
class Curl
{

    private static $connection;

    public static function doPostCookies( $url , $params , $cookies )
    {

        self::start($url);

        curl_setopt(self::$connection, CURLOPT_POST, true);
        curl_setopt(self::$connection, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt(self::$connection, CURLOPT_COOKIEJAR, $cookies);
        curl_setopt(self::$connection, CURLOPT_COOKIEFILE, $cookies);

        $result = json_decode(curl_exec(self::$connection), true);

        self::stop();

        return $result;

    }

    public static function doPostNoCookies( $url , $params )
    {

        self::start($url);

        curl_setopt(self::$connection, CURLOPT_POST, true);
        curl_setopt(self::$connection, CURLOPT_POSTFIELDS, http_build_query($params));

        $result = json_decode(curl_exec(self::$connection), true);

        self::stop();

        return $result;

    }

    private function start( $url )
    {

        self::$connection = curl_init();

        curl_setopt(self::$connection, CURLOPT_URL, $url);
        curl_setopt(self::$connection, CURLOPT_USERAGENT, "Stangbots (https://github.com/tiedemann77/stangbots). For more info, visit: https://stangbots.toolforge.org/.");
        curl_setopt(self::$connection, CURLOPT_RETURNTRANSFER, true);

    }

    private function stop()
    {
        curl_close(self::$connection);
    }

}

?>
