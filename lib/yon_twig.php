<?php

require_once "Twig/autoload.php";

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

function yon_setup_twig($tpl_dir)
{
	$loader = new Filesystemloader($tpl_dir);
	$twig = new Environment($loader);
	return $twig;
}

function yon_twig_add_function($twig, $name, $func)
{
	$twig_func = new \Twig\TwigFunction($name, $func);
	$twig->addFunction($twig_func);
}

function yon_twig_add_functions($twig, $funcs)
{
	foreach ($funcs as $name => $func)
	{
		yon_twig_add_function($twig, $name, $func);
	}
}

function yon_twig_add_filter($twig, $name, $func)
{
	$twig_filter = new \Twig\TwigFilter($name, $func);
	$twig->addFilter($twig_filter);
}

function yon_twig_add_filters($twig, $funcs)
{
	foreach ($funcs as $name => $func)
	{
		yon_twig_add_filter($twig, $name, $func);
	}
}

?>
