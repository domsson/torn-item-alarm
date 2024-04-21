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
define("TORN_DIR",     "data/torn");

$debug    = yon_setup_errors(DEBUG);
$encoding = yon_setup_utf8();
$sid      = yon_setup_session();
$uri      = yon_parse_url();
$twig     = yon_setup_twig(TEMPLATE_DIR);
$time     = time();

$page      = yon_json_file_load(CONFIG_DIR . "/page.json");
$version   = yon_json_file_load(CONFIG_DIR . "/version.json");
$torn      = yon_json_file_load(CONFIG_DIR . "/torn.json");
$whitelist = yon_json_file_load(CONFIG_DIR . "/whitelist.json");

$info = [];

$base_url = $uri["base"];
$config    = [
	"api_url" => "{$base_url}/api/item.php?item_id={item_id}&api_key={api_key}"
];

//
// check if we have a user record (based on session ID)
//

$user_filepath = null;
$user = yon_json_find_newest_file_by_prop(USER_DIR, "sid", $sid, $user_filepath);

if (!$user)
{
	$user = [];
	$user["sid"] = $sid;
}

//yon_dump_var($user);
//yon_dump_var($user_filepath);

//
// For convenience
//

$api_key   = $user["api_key"] ?? null;
$api_url   = $torn["api_url"];
$api_limit = $torn["api_limit"];

//
// Functions
//

function save_user_data_to_file($user, $filename_prop="player_id")
{
	if (!isset($user[$filename_prop]))
	{
		return false;
	}
	$filepath = USER_DIR . "/" . $user[$filename_prop] . ".json";
	return yon_json_file_save($filepath, $user);
}

function torn_fetch_user_info($api_url, $api_key)
{
	return yon_http_get("{$api_url}/user/?selections=basic&key={$api_key}&comment=itemalarm", null);
}

function torn_fetch_user_profile($api_url, $api_key)
{
	return yon_http_get("{$api_url}/user/?selections=profile&key={$api_key}&comment=itemalarm", null);
}

function torn_fetch_item_info($api_url, $api_key)
{
	return yon_http_get("{$api_url}/torn/?selections=items&key={$api_key}&comment=itemalarm", null);
}

// TODO also check if they are member of recruit of faction
//		"days_in_faction": 56,

//
// fetch item names and add them to the item JSON (TODO cache this!)
//

$torn_items_filepath = TORN_DIR . "/items.json";
$items = yon_json_file_load($torn_items_filepath);

// Invalidate items if the file is older than an hour
$items_age = $time - filemtime($torn_items_filepath);
if ($items_age > 3600) $items = false;

// TODO we also need to do this is the file on hand is too old (an hour or so?)
if ($items === false && $api_key)
{
	$item_info = torn_fetch_item_info($api_url, $api_key);

	if ($item_info && isset($item_info["items"]))
	{
		$items = $item_info["items"];
		yon_json_file_save($torn_items_filepath, $items); 
	}
}

if ($uri["slug"] == "login")
{
	$api_key = $_POST["api-key"] ?? "";
	$api_key = filter_var($_POST["api-key"], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
	if (empty($api_key))
	{
		yon_redirect("{$base_url}?login=failed&error=no_api_key");
	}


	$user_info = torn_fetch_user_info($api_url, $api_key);
	if (!$user_info or !isset($user_info["player_id"]))
	{
		yon_redirect("{$base_url}?login=failed&error=cant_fetch_user_info");
	}

	// try to find existing file by player_id, if they've logged in with same API key before
	$user_filepath = null;
	$user = yon_json_find_file_by_prop(USER_DIR, "player_id", $user_info["player_id"], $user_filepath);
	if (!$user) $user = [];

	$user["sid"]       = $sid;
	$user["api_key"]   = $api_key;
	$user["player_id"] = $user_info["player_id"];
	$user["name"]      = $user_info["name"];

	$user_profile = torn_fetch_user_profile($api_url, $api_key);
	if ($user_profile)
	{
		if (isset($user_profile["profile_image"]))
		{
			$user["profile_image"] = $user_profile["profile_image"];
		}
		if (isset($user_profile["faction"]))
		{
			$user["faction"] = $user_profile["faction"];
		}
	}

	$saves = save_user_data_to_file($user);
	yon_redirect($base_url);
}

if ($uri["slug"] == "logout")
{
	unset($user["api_key"]);
	unset($user["name"]);
	unset($user["faction"]);
	$save = save_user_data_to_file($user);
	yon_redirect($base_url);
}

if ($uri["slug"] == "add-item")
{
	$item_id = (int) filter_var($_POST["item-id"], FILTER_SANITIZE_NUMBER_INT);
	if ($user)
	{
		if (!isset($user["items"])) $user["items"] = [];
		$user["items"][$item_id] = [
			"alarm-price-model" => "market-value",
			"trade-price-model" => "market-value"
		];
		save_user_data_to_file($user);
	}
	yon_redirect($base_url);
}

if ($uri["slug"] == "remove-item")
{
	$item_id = (int) filter_var($_POST["item-id"], FILTER_SANITIZE_NUMBER_INT);
	if ($user && isset($user["items"][$item_id]))
	{
		unset($user["items"][$item_id]);
		save_user_data_to_file($user);
	}
	yon_redirect($base_url);
}

if ($uri["slug"] == "edit-item")
{
//	yon_dump_var($_POST);

	$item_id = (int) filter_var($_POST["item-id"], FILTER_SANITIZE_NUMBER_INT);
	$alarm_price = (int) filter_var($_POST["alarm-price"], FILTER_SANITIZE_NUMBER_INT);
	$trade_price = (int) filter_var($_POST["trade-price"], FILTER_SANITIZE_NUMBER_INT);
	$alarm_price_model = filter_var($_POST["alarm-price-model"], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
	$trade_price_model = filter_var($_POST["trade-price-model"], FILTER_SANITIZE_FULL_SPECIAL_CHARS);

	if ($user && isset($user["items"][$item_id]))
	{
		if ($alarm_price) $user["items"][$item_id]["alarm_price"] = $alarm_price;
		if ($trade_price) $user["items"][$item_id]["trade_price"] = $trade_price;
		if ($alarm_price_model) $user["items"][$item_id]["alarm_price_model"] = $alarm_price_model;
		if ($trade_price_model) $user["items"][$item_id]["trade_price_model"] = $trade_price_model;
		save_user_data_to_file($user);
	}
	yon_redirect($base_url);
}

if ($uri["slug"] == "settings")
{
	
}

//
// Check user access based on whitelist
//

$user["access"] = false;
if (isset($user["player_id"]) && $whitelist)
{
	if (isset($whitelist["players"]) && is_array($whitelist["players"]))
	{
		if (in_array($user["player_id"], $whitelist["players"]))
		{
			$user["access"] = true;
		}
	}
	if (isset($whitelist["factions"]) && is_array($whitelist["factions"]))
	{
		if (isset($user["faction"]) && isset($user["faction"]["faction_id"]))
		{
			if (in_array($user["faction"]["faction_id"], $whitelist["factions"]))
			{
				$user["access"] = true;
			}
		}
	}
}

// 
// assemble data and render the page
//
	
$options = [
	"get"     => $_GET,
	"post"    => $_POST,
	"page"    => $page,
	"version" => $version,
	"user"    => $user,
	"info"    => $info,
	"torn"    => $torn,
	"config"  => $config,
	"items"   => $items
];

echo $twig->render("default.html.twig", $options);

?>
