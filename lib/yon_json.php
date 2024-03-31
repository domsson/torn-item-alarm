<?php

//
// low level
// opening and saving json files
//

function yon_json_file_load($file, $depth=16, $flags=null)
{
	$data = @file_get_contents($file);
	if ($data === false) return false;

	if ($flags === null)
	{
		$flags = JSON_INVALID_UTF8_IGNORE |
			JSON_OBJECT_AS_ARRAY;
	}

	$decoded = json_decode($data, true, $depth, $flags);
	return $decoded === null ? false : $decoded;
}

function yon_json_file_save($file, $data, $flags=null)
{
	if ($flags === null)
	{
		$flags = JSON_INVALID_UTF8_IGNORE |
			JSON_UNESCAPED_SLASHES |
			JSON_UNESCAPED_UNICODE |
			JSON_PRESERVE_ZERO_FRACTION |
			JSON_PRETTY_PRINT;
	}

	$json = json_encode($data, $flags);
	if ($json === false) return false;

	return @file_put_contents($file, $json);
}

//
// low level
// editing json data, top-level properties
//

function yon_json_data_get_entry($json, $id)
{
	return $json[$id] ?? null;
}

function yon_json_data_set_entry(&$json, $id, $val)
{
	$json[$id] = $val;
}

function yon_json_data_del_entry(&$json, $id)
{
	unset($json[$id]);
}

//
// high level
// editing json data, file being opened and/or saved automatically
//

function yon_json_get_entry($file, $id)
{
	$json = yon_json_file_load($file);
	if ($json === false) return false;

	return yon_json_data_get_entry($json, $id);
}

function yon_json_set_entry($file, $id, $val)
{
	$json = yon_json_file_load($file);
	if ($json === false) return false;

	yon_json_data_set_entry($json, $id, $val);
	return yon_json_file_save($file, $json);
}

function yon_json_set_entries($file, $data)
{
	$json = yon_json_file_load($file);
	if ($json === false) return false;

	foreach ($data as $id => $val)
	{
		yon_json_data_set_entry($json, $id, $val);
	}
	return true;
}

function yon_json_del_entry($file, $id)
{
	$json = yon_json_file_load($file);
	if ($json === false) return false;

	yon_json_data_del_entry($json, $id);
	return yon_json_file_save($file, $json);
}

function yon_json_find_file_by_prop($dir, $prop_name, $prop_value, &$filepath=null)
{
	foreach (glob($dir . "/*.json") as $file)
	{
		$json = yon_json_file_load($file);

		if (!isset($json[$prop_name]))
		{
			continue;
		}

		if ($json[$prop_name] === $prop_value)
		{
			$filepath = $file;
			return $json;
		}
	}

	return null;
}

function yon_json_find_newest_file_by_prop($dir, $prop_name, $prop_value, &$filepath=null)
{
	$matches = [];

	foreach (glob($dir . "/*.json") as $file)
	{
		$json = yon_json_file_load($file);

		if (!isset($json[$prop_name]))
		{
			continue;
		}

		if ($json[$prop_name] === $prop_value)
		{
			$matches[] = [
				"file" => $file,
				"json" => $json,
				"time" => filemtime($file)
			];
		}
	}

	$newest_index = -1;
	$newest_time  = 0;
	foreach ($matches as $index => $match)
	{
		if ($match["time"] > $newest_time)
		{
			$newest_index = $index;
			$newest_time  = $match["time"];
		}	
	}

	if ($newest_index == -1)
	{
		return null;
	}

	$filepath = $matches[$newest_index]["file"];
	return $matches[$newest_index]["json"];
}

?>
