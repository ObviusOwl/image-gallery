<?php
require_once(__DIR__ . "/../src/autoload.php");
require_once(__DIR__ . "/../config/config.php");

try{
    $db = new ImageGallery\Database($conf);
    $api = new ImageGallery\Api($conf, $db);
}catch( ImageGallery\DatabaseError $e ){
    $aerr = new ImageGallery\ApiError($e->getMessage(), 500, $e);
    $aerr->display();
    exit;
}catch( \Exception $e ){
    $aerr = new ImageGallery\ApiError("Unhandled Exception: ".$e->getMessage(), 500, $e);
    $aerr->display();
    exit;
}

$api->dispatch();
?>