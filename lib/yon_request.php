<?php

function yon_ctype_by_method($method, $data=null)
{
	switch ($method)
	{
		case "GET":
		case "DELETE":
			return $data === false
				? null
				: "application/x-www-form-urlencoded; charset=utf-8";
		case "POST":
			return "application/x-www-form-urlencoded; charset=utf-8";
			break;
		case "PUT":
			return "application/json; charset=utf-8";
			break;
		case "PATCH":
			return "application/json-patch+json; charset=utf-8";
			break;
		default:
			return null;
	}
}

function yon_http_basic_auth($user, $pass)
{
	return "Basic " . base64_encode("$user:$pass");
}

function yon_http_build_header($headers)
{
	$header = "";
	foreach ($headers as $h)
	{
		$header .= "{$h}\r\n";
	}
	return $header;
}

/*
 * Input is either nothing or an array of strings ($http_response_header)
 * Output is either an empty array or an associative array
 */
function yon_http_parse_headers($header)
{
	$headers = [];
	if (!isset($header)) return $headers;
	if (!is_array($header)) return $headers;

	foreach ($header as $h)
	{
		$colon = strpos($h, ":");
		$key = ($colon === false) ? "Status" : substr($h, 0, $colon);
		$val = trim(substr($h, $colon));
		$headers[$key] = $val;
	}
	return $headers;
}

/*
 * Expects the first line of $http_response_header as input.
 * Returns 0 if no status code could be extracted, else the status code.
 */
function yon_http_parse_status($http_response_status)
{
	if (!isset($http_response_status)) return 0;
	if (empty($http_response_status)) return 0;

	// We expect something like "HTTP/1.1 200 OK"
	$tokens = explode(" ", $http_response_status);

	// Check for a second token and whether its numeric
	if (!isset($tokens[1])) return 0;
	if (!is_numeric($tokens[1])) return 0;

	return (int) $tokens[1];
}

/*
 * Makes a HTTP request and returns the result, possibly as JSON if appropriate.
 * On failure, `false` is returned. If provided, the `$response_code` varible
 * will be populated with the HTTP response code, if any.
 */
function yon_http_request($url, $method, $data=null, $auth=null, $ctype=null, $headers=null, &$response_code=null)
{
	$header  = "";
	$content = "";
	$method  = strtoupper(trim($method));

	if ($ctype === null)
	{
		$ctype = yon_ctype_by_method($method, $data);
	}

	if ($ctype !== null)
	{
		$header .= "Content-Type: {$ctype}\r\n";
	}

	if ($auth !== null)
	{
		$header .= "Authorization: {$auth}\r\n";
	}

	if ($headers !== null)
	{
		$header .= is_array($headers) ? 
			yon_http_build_header($headers) :
			$headers;
	}

	if ($data !== null)
	{
		$content = is_array($data) ?
			http_build_query($data) :
			$data;
	}

	$options = [
		"http" => [
			"method"  => $method,
			"ignore_errors" => true
		]
	];

	if ($header !== "")
	{
		$options["http"]["header"] = $header;
	}

	if ($content !== "")
	{
		if ($method == "GET")
		{
			$url .= "?{$content}";
		}
		else
		{
			$options["http"]["content"] = $content;
		}
	}

	// Actually make the request
	$context = stream_context_create($options);
	$result  = file_get_contents($url, false, $context);
	
	// $http_response_header is PHP magic - it will be set after a HTTP request
	$response_head = yon_http_parse_headers($http_response_header);
	$response_code = yon_http_parse_status($response_head["Status"]);

	if ($result === null)  return null;
	if ($result === false) return false;

	$ctype = $response_head["Content-Type"] ?? false;
	$is_json = $ctype && stripos($ctype, "application/json");

	return $is_json ? json_decode($result, true) : $result;
}

//
// Convenience methods - shortcuts with more defaults, less control
//

function yon_http_get($url, $data=null, $auth=null, $ctype=null, &$status=null)
{
	return yon_http_request($url, "GET", $data, $auth, $ctype, null, $status);
}

function yon_http_post($url, $data=null, $auth=null, $ctype=null, &$status=null)
{
	return yon_http_request($url, "POST", $data, $auth, $ctype, null, $status);
}

function yon_http_put($url, $data=null, $auth=null, $ctype=null, &$status=null)
{
	return yon_http_request($url, "PUT", $data, $auth, $ctype, null, $status);
}

function yon_http_delete($url, $data=null, $auth=null, $ctype=null, &$status=null)
{
	return yon_http_request($url, "DELETE", $data, $auth, $ctype, null, $status);
}

?>
