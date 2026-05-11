<?php
declare(strict_types=1);

use LuandaVAT\views\HomeView;
use LuandaVAT\views\AuthView;
use LuandaVAT\views\SourceView;
use LuandaVAT\views\ContactView;

require_once 'vendor/autoload.php';

$paths = explode('/', trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/'));

$path = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
$path = trim(str_replace($paths[0], '', $path), '/');

$theme = $_COOKIE['theme'] ?? 'dark';

if (strtolower($path) === 'login') {
	$authView = new AuthView($theme);
	$authView->createPage()->Show();
} else if (strtolower($path) === 'contact') {
	$contactView = new ContactView($theme);
	$contactView->createPage()->Show();
} else if (strtolower($path) === 'source') {
	$sourceView = new SourceView($theme);
	$sourceView->createPage()->Show();
} else {
	var_dump($path);
}

?>