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

$page = yon_json_file_load(CONFIG_DIR . "/page.json");
$torn = yon_json_file_load(CONFIG_DIR . "/torn.json");
// $user = $_SERVER["REMOTE_USER"] ?? $_SERVER["PHP_AUTH_USER"];
$info = [];

//yon_dump_var($uri);

//
// check if we have a user record (based on session ID)
//

$user_dir = USER_DIR;
$user_filepath = null;
$user = yon_json_find_file_by_prop($user_dir, "sid", $sid, $user_filepath);

if (!$user)
{
	$user = [];
	$user["sid"] = $sid;
}

yon_dump_var($user);

$api_key   = $user["api_key"] ?? null;
$api_url   = $torn["api_url"];
$api_limit = $torn["api_limit"];

//
// functions
//

function save_user_data_to_file($user)
{
	if (!isset($user["api_key"]))
	{
		return false;
	}
	$filepath = USER_DIR . "/" . $user["api_key"] . ".json";
	return yon_json_file_save($filepath, $user);
}

//
// DUDE! WE CAN GETCH USER ID EVEN WITH PUBLIC KEY!
// https://api.torn.com/user/?selections=basic&key=
// $result["name"] = domsson
// $result["player_id"] = 3206827
//
// Can also get profile image:
// https://api.torn.com/user/?selections=profile&key=
// $result["profile_image"] = "https://profileimages.torn.com/3c701333-7063-4911-b5d2-438fc767ec24-3206827.png"

//
// fetch item names and add them to the item JSON (TODO cache this!)
//

$torn_items_filepath = TORN_DIR . "/items.json";
//$torn_items_filepath = ROOT_DIR + "/" + TORN_DIR + "/items.json";
$items = yon_json_file_load($torn_items_filepath);

if ($items === false && $api_key)
{
	$item_info = yon_http_get("{$api_url}/torn/?selections=items&key={$api_key}", null);

	if ($item_info && isset($item_info["items"]))
	{
		$items = $item_info["items"];
		yon_json_file_save($torn_items_filepath, $items); 
	}
}

if ($uri["slug"] == "login")
{
	if (isset($_POST["api-key"]))
	{
		$api_key = filter_var($_POST["api-key"], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		$user["api_key"] = $api_key;
		save_user_data_to_file($user);
		// TODO figure out while yon sees the URL as https (when it should be http)
		// 	and once that's fixed, use $uri["base"] for the redirect!
	}
	yon_redirect("http://itemalarm.halfpast.one");
}

if ($uri["slug"] == "add-item")
{
	$item_id = (int) filter_var($_POST["add-item-id"], FILTER_SANITIZE_NUMBER_INT);
	if ($user)
	{
		if (!isset($user["items"])) $user["items"] = [];
		$user["items"][$item_id] = [];
		save_user_data_to_file($user);
		yon_redirect($uri["base"]);
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
