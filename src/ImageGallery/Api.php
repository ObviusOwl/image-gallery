<?php 
namespace ImageGallery;

class Api{
    
    protected $conf = null;
    protected $router = null;
    protected $db = null;
    protected $browser = null;
    
    public function __construct(Config $conf, Database $db){
        $this->conf = $conf;
        $this->db = $db;
        $this->router = new Router();
        $this->browser = new FileBrowser($this->conf, $this->db);
        
        $this->view = new ApiView($this->conf, $this->db, $this->browser);
        $this->controller = new ApiController($this->conf, $this->db, $this->browser);
        
        $r = $this->router;
        $r->route("POST", "/filebrowser/files", [ $this, "postFilebrowserFiles" ]);
        $r->route("POST", "/filebrowser/thumbnail", [ $this, "postFilebrowserThumb" ]);

        $r->route("POST", "/galleries", [ $this, "postGallery" ]);
        $r->route("GET", "/galleries/:id", [ $this, "getGallery" ]);
        $r->route("PATCH", "/galleries/:id", [ $this, "patchGallery" ]);
        $r->route("DELETE", "/galleries/:id", [ $this, "deleteGallery" ]);
        
        $r->route("GET", "/galleries/:id/thumbnails", [ $this, "getGalleryThumbnails" ]);
        $r->route("PUT", "/galleries/:id/thumbnails", [ $this, "putGalleryThumbnails" ]);
        
        $r->route("GET", "/galleries/:id/files", [ $this, "getGalleryFiles" ]);
        $r->route("PUT", "/galleries/:id/files", [ $this, "putGalleryFiles" ]);
        
        $r->route("GET", "/files/:id", [ $this, "getFile" ]);
        $r->route("PATCH", "/files/:id", [ $this, "patchFile" ]);

        $r->default([ $this, "optionsCallback" ], "OPTIONS");
        $r->default( function($r){ return new ApiError( "API '{$r->path}' not found", 404 ); } );
    }
    
    public function dispatch(){
        try{
            $res = $this->router->dispatch();
        }catch ( ApiError $e ){
            $res = $e;
        }catch ( Exception $e ){
            $res = new ApiError("Unhandled Exception: ".$e->getMessage(), 500, $e);
        }
        
        if( $res === null ){
            $res = new ApiError("NULL result", 500);
        }else if( ! ($res instanceof ApiResponse) ){
            $res = new ApiError("Invalid response type", 500);
        }

        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Credentials: false");
        
        $res->display();
    }
    
    public function requireLogin(){
        // Note: do not use cookies/session here, due to CORS
        // https://www.php.net/manual/en/book.oauth.php
        if( $this->conf[ "app.login.readonly" ] ){
            throw new ApiError("app is in readonly mode", 403);
        }
    }

    protected function loadGallery($id, bool $loadThumbs=false, bool $loadFiles=false){
        $gallery = $this->controller->loadGallery($id);
        if( $loadThumbs ){
            $this->db->galleryLoadThumbnails($gallery);
        }
        if( $loadFiles ){
            $this->db->galleryLoadFiles($gallery);
        }
        return $gallery;
    }
    
    public function optionsCallback(Request $req){
        if( isset($_SERVER["HTTP_ACCESS_CONTROL_REQUEST_METHOD"]) ){
            header("Access-Control-Allow-Methods: POST, GET, OPTIONS, DELETE, PUT");
        }

        if( isset($_SERVER["HTTP_ACCESS_CONTROL_REQUEST_HEADERS"]) ){
            header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
        }
        return new ApiResponseJson( [], 200 );
    }
    
    public function postFilebrowserFiles(Request $req){
        $this->requireLogin();
        $data = $req->getBodyAsJson();
        if( !isset($data["path"]) ){
            throw new ApiError("property 'path' is missing", 400);
        }
        $path = new Path($data["path"]);

        try{
            $parent = $this->browser->getFile($path);
            $files = $this->browser->listFiles($path);
        }catch( FileNotFoundError $e ){
            throw new ApiError("File not found", 404);
        }
        
        return new ApiResponseJson( $this->view->viewDirectory($parent, ...$files), 200 );
    }
    
    public function postFilebrowserThumb(Request $req){
        $this->requireLogin();
        $data = $req->getBodyAsJson();
        if( !isset($data["path"]) ){
            throw new ApiError("property 'path' is missing", 400);
        }
        if( !isset($data["size"]) || ! isset(Thumbnailer::THUMBNAIL_SIZES[$data["size"]]) ){
            throw new ApiError("property 'size' is missing or invalid", 400);
        }

        $file = $this->controller->getFileByPath($data["path"]);
        $thumbs = $this->controller->updateFileThumbnails($file, $data["size"]);
        
        $data = [ 
            "thumbnails" => array_map( [$this->view, "viewThumbnail"], $thumbs)
        ];
        return new ApiResponseJson( $data, 200 );
    }

    public function getGallery(Request $req){
        $gallery = $this->loadGallery($req->getPathVar("id"), true, true);
        return new ApiResponseJson( $this->view->viewGallery($gallery), 200 );
    }
    
    public function postGallery(Request $req){
        $this->requireLogin();
        // TODO use given thumbnail file ids and/or generate/autoadd thumbnails
        $data = $req->getBodyAsJson();
        $gallery = new Gallery();
        
        $this->controller->updateGallery($gallery, $data);

        if( isset($data["files"]) ){
            if( ! is_array($data["files"]) ){
                throw new ApiError("property 'files' must be an array", 400);
            }
            $files = [];
            foreach( $data["files"] as $fData ){
                if( isset($fData["id"]) ){
                    $files[] = $this->controller->getFileById($fData["id"]);
                }else if( isset($fData["path"]) ){
                    $file = $this->controller->getFileByPath($fData["path"]);
                    $files[] = $this->db->getOrCreateFile($file);
                }else{
                    throw new ApiError("property 'files[].id' or 'files[].path' must be given", 400);
                }
            }

            $gallery->setFiles(...$files);
        }
        
        $this->db->addGallery($gallery);
        
        return new ApiResponseJson( $this->view->viewGallery($gallery), 200 );
    }
    
    public function patchGallery(Request $req){
        $this->requireLogin();
        $data = $req->getBodyAsJson();
        $gallery = $this->loadGallery($req->getPathVar("id"), false, false);
        $gallery->clearTaint();

        $this->controller->updateGallery($gallery, $data);
        $this->db->updateGallery($gallery);
        
        return new ApiResponseJson( $this->view->viewGallery($gallery), 200 );
    }
    
    public function deleteGallery(Request $req){
        $this->requireLogin();
        $data = $req->getBodyAsJson();
        $gallery = $this->loadGallery($req->getPathVar("id"), false, false);
        
        try{
            $this->db->deleteGallery($gallery->getId());
        }catch( DatabaseError $e ){
            $err = new ApiError("Failed to delete gallery", 500, $e);
            $files = $this->db->getFilesLinkedToGallery($gallery->getId());
            $data = [ "references" => [] ];
            foreach($files as $file){
                $data["references"][] = [
                    "type" => $file->getType(), 
                    "id" => $file->getId(),
                    "path" => strval($file->getVirtualPath()),
                ];
            }
            $err->addPayload($data);
            throw $err;
        }
        return new ApiResponseJson( [], 200 );
    }
    
    public function getGalleryThumbnails(Request $req){
        $gallery = $this->loadGallery($req->getPathVar("id"), true, false);
        $files = $gallery->getThumbnails();
        return new ApiResponseJson( $this->view->viewGalleryThumbnails(...$files), 200 );
    }
    
    public function putGalleryThumbnails(Request $req){
        $this->requireLogin();
        $data = $req->getBodyAsJson();
        $gallery = $this->loadGallery($req->getPathVar("id"), false, false);
        
        $fileIds = [];
        if( !isset($data["thumbnails"]) || ! is_array($data["thumbnails"]) ){
            throw new ApiError("property 'thumbnails' is missing or not an array", 400);
        }

        $filesData = [];
        foreach( $data["thumbnails"] as $fData ){
            if( !isset($fData["file"]) ){
                throw new ApiError("property 'thumbnails[].file' is missing", 400);
            }
            $filesData[] = $fData["file"];
        }

        foreach( $filesData as $fData ){
            if( isset($fData["id"]) ){
                if( ! is_numeric($fData["id"]) ){
                    throw new ApiError("property 'thumbnails[].file.id' must be an integer", 400);
                }
                $fileIds[] = intval($fData["id"]);
            }else if( isset($fData["path"]) ){
                $file = $this->controller->getFileByPath($fData["path"]);
                $file = $this->db->getOrCreateFile($file);
                $fileIds[] = $file->getId();
            }else{
                throw new ApiError("property 'file.id' or 'file.path' must be given", 400);
            }
        }
        
        $this->db->gallerySetThumbnails($gallery, ...$fileIds);
        $this->db->galleryLoadThumbnails($gallery);

        $files = $gallery->getThumbnails();
        return new ApiResponseJson( $this->view->viewGalleryThumbnails(...$files), 200 );
    }

    public function getGalleryFiles(Request $req){
        $gallery = $this->loadGallery($req->getPathVar("id"), false, true);
        $files = $gallery->getFiles();
        return new ApiResponseJson( $this->view->viewFiles(...$files), 200 );
    }
    
    public function putGalleryFiles(Request $req){
        $this->requireLogin();
        $data = $req->getBodyAsJson();
        $gallery = $this->loadGallery($req->getPathVar("id"), false, false);
        
        $fileIds = [];
        if( !isset($data["files"]) || ! is_array($data["files"]) ){
            throw new ApiError("property 'files' is missing or not an array", 400);
        }
        foreach( $data["files"] as $fData ){
            if( isset($fData["id"]) ){
                if( ! is_numeric($fData["id"]) ){
                    throw new ApiError("property 'files[].id' must be an integer", 400);
                }
                $fileIds[] = intval($fData["id"]);
            }else if( isset($fData["path"]) ){
                $file = $this->controller->getFileByPath($fData["path"]);
                $file = $this->db->getOrCreateFile($file);
                $fileIds[] = $file->getId();
            }else{
                throw new ApiError("property 'files[].id' or 'files[].path' must be given", 400);
            }
        }
        
        $this->db->gallerySetFiles($gallery, ...$fileIds);

        $files = $gallery->getFiles();
        return new ApiResponseJson( $this->view->viewFiles(...$files), 200 );
    }
    
    public function getFile(Request $req){
        $file = $this->controller->getFilebyId($req->getPathVar("id"));
        return new ApiResponseJson( $this->view->viewFile($file), 200 );
    }

    public function patchFile(Request $req){
        $this->requireLogin();
        $data = $req->getBodyAsJson();
        $file = $this->controller->getFilebyId($req->getPathVar("id"));
        $file->clearTaint();

        $this->controller->updateFile($file, $data);
        $this->db->updateFile($file);
        
        return new ApiResponseJson( $this->view->viewFile($file), 200 );
    }
    
    
}