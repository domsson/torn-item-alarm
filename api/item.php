<?php

require_once "../lib/yon_core.php";
require_once "../lib/yon_json.php";
require_once "../lib/yon_request.php";

define("MARKET_DIR", "../cache/market");

$item_id = yon_get_http_var("item_id", FILTER_VALIDATE_INT);
$api_key = yon_get_http_var("api_key");

$api_url = "https://api.torn.com/market/${item_id}";
$api_data = [
	"selections" => "bazaar,itemmarket",
	"key" => $api_key,
	"comment" => "itemalarm"	
];

$filepath = MARKET_DIR . "/${item_id}.json";
$data = null;

if (file_exists($filepath))
{
	$age = time() - filemtime($filepath);
	if ($age < 2)
	{
		$data = yon_json_file_load($filepath);
	}
}

if (empty($data))
{
	$data = yon_http_get($api_url, $api_data);
	yon_json_file_save($filepath, $data);
}

yon_echo_and_exit($data, true, true);

?>
