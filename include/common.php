<?php
session_start();
date_default_timezone_set('America/Denver');
require_once("settings.php");

require_once("Mustache/Autoloader.php");
Mustache_Autoloader::register();

$m = new Mustache_Engine([
    'pragmas' => [Mustache_Engine::PRAGMA_BLOCKS],
    'loader' => new Mustache_Loader_FilesystemLoader(dirname(__FILE__) . '/../views'),
]);

require_once('MysqliDb.php');
$db = new MysqliDb ($host, $username, $password, $dbname);

$data['settings']['year'] = date('Y');
$data['settings']['GMapAPI'] = $gmapsAPI;

if ($_SESSION['user']['admin'] == 1) {
    $data['admin'] = true;
}

if (!empty($_SESSION)) {
    $data['user'] = $_SESSION['user'];
}
