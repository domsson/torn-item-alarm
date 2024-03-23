<?php

// https://api.torn.com/market/1087?selections=bazaar,itemmarket&key=bcNz1mj6LedkuUoZ

require_once "lib/yon_core.php";
require_once "lib/yon_request.php";
require_once "lib/yon_json.php";
require_once "lib/yon_twig.php";

define("DEBUG",        true);
define("ROOT_DIR",     realpath(__DIR__));
define("CONFIG_DIR",   "config");
define("DATA_DIR",     "data");
define("TEMPLATE_DIR", "templates");
define("USER_DIR",     "data/users");

$debug    = yon_setup_errors(DEBUG);
$encoding = yon_setup_utf8();
$sid      = yon_setup_session();
$uri      = yon_parse_url();
$twig     = yon_setup_twig(TEMPLATE_DIR);

$page = yon_json_file_load(CONFIG_DIR . "/page.json");
$torn = yon_json_file_loaD(CONFIG_DIR . "/torn.json");
$user = $_SERVER["REMOTE_USER"] ?? $_SERVER["PHP_AUTH_USER"];
$info = [];
$items = yon_json_file_load(CONFIG_DIR . "/items.json"); 

//
// check if we have a user record (based on session ID)
//

//$user = yon_json_find_file_by_prop($dir, $prop_name, $prop_value, &$filepath)

$api_key   = $torn["api_key"];
$api_url   = $torn["api_url"];
$api_limit = $torn["api_limit"];

//
// check if we're below the API rate limit ($api_limit is "max request/second")
//

$req_max = 0;
foreach ($items as $item)
{
	$req_max += 1.0 / $item["interval"];
}
$info["req_per_sec"] = $req_max;

//
// fetch item names and add them to the item JSON (TODO cache this!)
//

$item_info = yon_http_get("{$api_url}/torn/?selections=items&key={$api_key}", null);

if ($item_info && isset($item_info["items"]))
{
	foreach ($items as $key => $item)
	{
		$item_id = $item["id"];
		if (isset($item_info["items"][$item_id]))
		{
			$items[$key] = array_merge($item, $item_info["items"][$item_id]);
		}
	}
}

// 
// assemble data and render the page
//
	
$options = [
	"get"   => $_GET,
	"post"  => $_POST,
	"page"  => $page,
	"user"  => $user,
	"info"  => $info,
	"torn"  => $torn,
	"items" => $items
];

echo $twig->render("default.html.twig", $options);

?>
