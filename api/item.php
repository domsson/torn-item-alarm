<?php

require_once "../lib/yon_core.php";
require_once "../lib/yon_json.php";
require_once "../lib/yon_request.php";

$item_id = yon_get_http_var("item_id", FILTER_VALIDATE_INT);
$api_key = yon_get_http_var("api_key");

$api_url = "https://api.torn.com/market/${item_id}";
$api_data = [
	"selections" => "bazaar,itemmarket",
	"key" => $api_key 
];

$result = yon_http_get($api_url, $api_data);
yon_echo_and_exit($result, true, true);

?>
