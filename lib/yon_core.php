<?php

function yon_is_in_debug($debug=false)
{
	return isset($_GET["debug"]) || $debug;
}

function yon_setup_errors($debug=false)
{
	$in_debug = yon_is_in_debug($debug);
	
	ini_set("display_errors", (int) $in_debug);
	ini_set("display_startup_errors", (int) $in_debug);
	error_reporting($in_debug ? E_ALL : E_ERROR);

	return $in_debug;
}

function yon_setup_utf8()
{
	@ini_set("default_charset", "UTF-8");
	mb_internal_encoding("UTF-8");
	return mb_internal_encoding();
}

function yon_setup_session($lifetime=2147483647)
{
	session_set_cookie_params($lifetime);
	session_start();
	return session_id();
}

function yon_dump_var($var, $class="")
{
	echo '<pre class="'. $class .'">';
	var_dump($var);
	echo '</pre>';
}

function yon_log($message, $file="./log.txt", $level="notice")
{
	$date = date("Y-m-d H:i:s");
	$line = "$date [$level] $message" . PHP_EOL;
	file_put_contents($file, $line, FILE_APPEND);
}

function yon_echo_and_exit($data, $encode_as_json=true, $send_json_header=true)
{
	if ($encode_as_json)
	{
		$flags = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
		$data = json_encode($data, $flags);
	}
	if ($send_json_header)
	{
		header('Content-Type: application/json; charset=utf-8');
	}
	echo $data;
	exit();
}

function yon_redirect($url, $status=303)
{
	header("Location: " . $url, true, $status);
	exit();
}

function yon_is_https($https_port=443)
{
	$scheme = isset($_SERVER["REQUEST_SCHEME"]) ?
	       	strtolower($_SERVER["REQUEST_SCHEME"]) : null;
	$https  = isset($_SERVER["HTTPS"]) ? 
		strtolower($_SERVER["HTTPS"]) : null;
	$port   = isset($_SERVER["SERVER_PORT"]) ?
	       	(int) $_SERVER["SERVER_PORT"] : null;

	if ($scheme === "https") return true;
	if ($https  === "on") return true;
	if ($https_port && ($port === $https_port)) return true;
	return false;
}

function yon_get_full_url()
{
	$scheme = yon_is_https() ? "https" : "http";
	return $scheme . "://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"];
}

function yon_parse_url($url="")
{
	if (!$url) $url = yon_get_full_url();

	$path = trim($url);
	$parsed = parse_url(html_entity_decode($path));
	
	$scheme   = $parsed["scheme"]   ?? null;
	$host     = $parsed["host"]     ?? null;
	$port     = $parsed["port"]     ?? null;
	$user     = $parsed["user"]     ?? null;
	$pass     = $parsed["pass"]     ?? null;
	$path     = $parsed["path"]     ?? null;
	$query    = $parsed["query"]    ?? null;
	$fragment = $parsed["fragment"] ?? null;

	$base = $scheme . "://" . $host . ($port ? ":" : "") . $port;

	// path elements ("/foo/bar/") and path-based arguments ("/foo:bar/")
	$paths = [];
	$args = [];

	$tokens = explode("/", $path);
	foreach ($tokens as $token)
	{
		if (trim($token) === "")
		{
			continue;
		}
		if (strpos($token, ":") !== false)
		{
			$arg = explode(":", $token);
			$args[$arg[0]] = $arg[1];
			continue;
		}	
		$paths[] = $token;
	}

	// last element of the path is the slug
	$num_path_elements = count($paths);
	$slug = $num_path_elements ? $paths[$num_path_elements - 1] : null;

	// GET params ("?foo=bar&baz=lol")
	$params = [];
	parse_str($query ?? "", $params);

	return [
		"url"       => $url,
		"base"      => $base,
		"scheme"    => $scheme,
		"host"      => $host, 
		"port"      => $port,
		"path"      => implode("/", $paths),
		"paths"     => $paths,
		"slug"      => $slug,
		"params"    => $params,
		"args"      => $args,
		"query"     => $query,
		"fragment"  => $fragment
	];
}

function yon_parse_accept_language($accept_language)
{
	$langs = [];
	$lang_strings = explode(",", $accept_language);

	foreach($lang_strings as $lang_string)
	{
		$lang_tokens = explode(";q=", $lang_string);
		$code = substr($lang_tokens[0], 0, 2);
		$pref = isset($lang_tokens[1]) ? (float) $lang_tokens[1] : 1.0;
		$langs[$code] = $pref;
	}

	arsort($langs, SORT_NUMERIC);

	return $langs;
}

function yon_get_language($candidates, $default, $path=null)
{
	if ($path && in_array($path, $candidates, true)) return $path;

	if (!isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) return $default;

	// parse accept language, sort it, look for match, return

	$langs = yon_parse_accept_language($_SERVER['HTTP_ACCEPT_LANGUAGE']);

	foreach ($langs as $lang => $q)
	{
		if (in_array($lang, $candidates, true)) return $lang;
	}

	return $default;
}

?>
