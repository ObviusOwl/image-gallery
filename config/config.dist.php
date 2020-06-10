<?php

$conf = new ImageGallery\Config();

$conf[ "app.path.thumbnail_dir" ] = "/srv/thumbnails/";
$conf[ "app.path.image_dir" ] = "/srv/images/";

$conf[ "app.url.app_base" ] = "http://localhost:8002/";
$conf[ "app.url.thumbnail_base" ] = "http://localhost:8002/thumbnails/";
$conf[ "app.url.image_base" ] = "http://localhost:8002/images/";

$conf[ "app.db.dbname" ] = "bilder";
$conf[ "app.db.host" ] = "db";
$conf[ "app.db.port" ] = 3306;
$conf[ "app.db.user" ] = "bilder";
$conf[ "app.db.password" ] = "bilder";

$conf[ "app.login.readonly" ] = false;