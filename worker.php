<?php
/**
 * Daemon script for running the SMS api
 */

include_once 'system/config.php';
include_once 'system/request.php';
include_once 'system/database.php';
include_once 'api/api_controller.php';

error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);
ini_set('display_errors', true);


if (!array_key_exists('HTTP_ORIGIN', $_SERVER)) {
    $_SERVER['HTTP_ORIGIN'] = $_SERVER['SERVER_NAME'];
}

// get the HTTP method, path and body of the request
Request::init();

// instantiate database and product object
$database = new Database($config_database);
$db = $database->getConnection();
$api = New API($db);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

$api->queueCheck($params);
