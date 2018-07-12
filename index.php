<?php

//include_once 'system/config.php';
include_once 'system/request.php';


if (!array_key_exists('HTTP_ORIGIN', $_SERVER)) {
    $_SERVER['HTTP_ORIGIN'] = $_SERVER['SERVER_NAME'];
}

// get the HTTP method, path and body of the request
Request::init();

$paths = explode("/", Request::$requestPath, 2);

if($paths[0] !== 'queue'){
    pageNotfound();
    exit();
}



function pageNotfound(){
    header('HTTP/1.0 404 Not Found');
    echo json_encode(
        array(
            "status" => "error",
            "message" => "page not found."
        )
    );
}
?>
