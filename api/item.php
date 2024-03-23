<?php

require_once "../lib/yon_core.php";
require_once "../lib/yon_json.php";
require_once "../lib/yon_request.php";

if (!isset($_GET["item_id"])) exit;
$item_id = (int) $_GET["item_id"];

$api_url = "https://api.torn.com/market/{item_id}";
$api_data = [
	"selections" => "bazaar,itemmarket",
	"key" => "bcNz1mj6LedkuUoZ" // TODO load from JSON
];

$api_url = str_replace("{item_id}", $item_id, $api_url);
$result = yon_http_get($api_url, $api_data);

header('Content-Type: application/json; charset=utf-8');
yon_echo_and_exit($result, true);

?>
