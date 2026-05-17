<?php
declare(strict_types=1);

use LuandaVAT\views\HomeView;
use LuandaVAT\views\AuthView;
use LuandaVAT\views\SourceView;
use LuandaVAT\views\ContactView;
use LuandaVAT\views\DashboardView;

require_once 'vendor/autoload.php';

$is_localhost = in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1']);

if ($is_localhost) {
	$paths = explode('/', trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/'));
	$path = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
	$path = trim(str_replace($paths[0], '', $path), '/');
} else {
	$path = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
}

$theme = $_COOKIE['theme'] ?? 'dark';
$status = $_SERVER['REDIRECT_STATUS'] ?? 200;

if ($status >= 400 && $status <= 500) {
	echo "<h1>Error</h1><p>You are not allowed here.</p>";
} else if (strtolower($path) === 'login') {
	$authView = new AuthView($theme);
	$authView->pageContract->csrfToken='test';
	$authView->createPage()->Show();
} else if (strtolower($path) === 'contact') {
	$contactView = new ContactView($theme);
	$contactView->createPage()->Show();
} else if (strtolower($path) === 'source') {
	$sourceView = new SourceView($theme);
	$sourceView->createPage()->Show();
} else {
	$dashView = new DashboardView($theme);
	$dashView->createPage()->Show();
}

?>