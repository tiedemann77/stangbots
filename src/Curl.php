<?php

/*
		***THIS FILE INCLUDES CODE FROM MEDIAWIKI API DEMOS THAT ARE
		LICENSED UNDER MIT LICENSE***
*/

// Classe do cURL
class Curl {

	public static function doPostRequest($url, $params, $cookies){

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
		curl_setopt($ch, CURLOPT_USERAGENT, "Stangbots (https://github.com/tiedemann77/stangbots). For more info, visit: https://stangbots.toolforge.org/.");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_COOKIEJAR, $cookies);
		curl_setopt($ch, CURLOPT_COOKIEFILE, $cookies);

		$result = json_decode(curl_exec($ch),true);
		curl_close($ch);
		return $result;

	}

}

?>
