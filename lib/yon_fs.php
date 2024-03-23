<?php

function yon_get_directories($dir)
{
	$dirs = glob($dir . '/*' , GLOB_ONLYDIR);

	return $dirs;
}

?>
