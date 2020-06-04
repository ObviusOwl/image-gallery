<?php

spl_autoload_register(function ($class){
    $projectNS = "ImageGallery";

    if( substr($class, 0, strlen($projectNS)+1) !== $projectNS."\\" ){
        // not our class, continue loading
        return;
    }
    if( strpos($class, '.') !== false ){
        # do not allow malicious/bogus '.' and '..' in the path
        return;
    }
        
    # convert ns separator to fs separator
    $relPath = str_replace('\\', '/', $class);
    # file name must be exactly the class name
    $fileName = __DIR__ . "/$relPath.php";
    
    if( file_exists($fileName) ){
        require( $fileName );
    }
});