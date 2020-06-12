<?php
require_once(__DIR__ . "/../src/autoload.php");
require_once(__DIR__ . "/../config/config.php");

$galleryId = 1;
if( isset($_GET["gallery"]) && is_numeric($_GET["gallery"]) ){
    $galleryId = intval($_GET["gallery"]);
}

try{
    $db = new ImageGallery\Database($conf);
    $browser = new ImageGallery\FileBrowser($conf, $db);
    $view = new ImageGallery\ApiView($conf, $db, $browser);

    $gallery = $db->getGalleryById($galleryId);
    if( $db === null ){
        throw new ImageGallery\ApiError("gallery not found", 404, $e);
    }
    $db->galleryLoadThumbnails($gallery);
    $db->galleryLoadFiles($gallery);
    
    $data = $view->viewGallery($gallery);
    
}catch( ImageGallery\DatabaseError $e ){
    error_log($e->getMessage());
    http_response_code($e->getCode());
    die("api error");
}catch( ImageGallery\ApiError $e ){
    error_log($e->getMessage());
    http_response_code(500);
    die("database error");
}catch( Exception $e ){
    error_log($e->getMessage());
    http_response_code(500);
    die("error");
}
?>
<!doctype html>
<html>
<head lang='en'>
    <meta charset='utf-8'>
    <title>test</title>

    <script type="module">
        import {default as main, store} from './assets/bundle.js';
        
        main();
        
        let gallery = JSON.parse('<?php echo addcslashes(json_encode($data),'\'\\'); ?>');
        store.setGallery(gallery);
    </script>

    <link rel="stylesheet" href="assets/bundle.css">

    <style>
        body{
            max-width: 1024px;
            margin: 1em auto;
        }
    </style>
</head>
<body>
    <div id="app"></div>
</body>
</html>