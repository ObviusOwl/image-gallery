<?php 
namespace ImageGallery;

class ApiController{
    
    protected $conf = null;
    protected $db = null;
    protected $browser = null;
    
    public function __construct(Config $conf, Database $db, FileBrowser $browser){
        $this->conf = $conf;
        $this->db = $db;
        $this->browser = $browser;
    }

    public function loadGallery($id){
        if( $id === null || ! is_numeric($id) ){
            throw new ApiError("Gallery ID must be numeric", 400);
        }
        $id = intval($id);
        
        $gallery = $this->db->getGalleryById($id);
        if( $gallery === null ){
            throw new ApiError("Gallery $id not found", 404);
        }
        return $gallery;
    }
    
    public function getFileByPath($path){
        if( $path === null || ! is_string($path) ){
            throw new ApiError("Path must be string", 400);
        }
        $path = new Path($path);
        
        try{
            return $this->browser->getFile($path);
        }catch( FileNotFoundError $e ){
            throw new ApiError("File '$path' not found", 404);
        }
    }

    public function getFileById($id){
        if( $id === null || ! is_numeric($id) ){
            throw new ApiError("File id must be integer", 400);
        }

        $file = $this->db->getFileById(intval($id));
        if( $file === null ){
            throw new ApiError("file $id not found", 404);
        }
        return $file;
    }
    
    public function updateFileThumbnails(File $file, string $sizeName){
        $thumbnailer = new Thumbnailer($this->conf);
        try{
            $thumbs = $thumbnailer->thumb($file, $sizeName);
            for( $i=0; $i<count($thumbs); $i++ ){
                $thumbs[$i] = $thumbnailer->update($thumbs[$i]);
            }
        }catch( ThumbnailError $e ){
            error_log($e->getMessage());
            throw new ApiError("Error generating thumbnail", 500);
        }
        return $thumbs;
    }
    
    public function updateGallery(Gallery $gallery, array $data){
        if( isset($data["name"]) ){
            $gallery->setName($data["name"]);
        }
        if( isset($data["path"]) ){
            $path = ( new Path($data["path"]) )->makeAbsolute()->resolve();
            $basePath = new Path("/galleries");
            if( ! $basePath->isParentOf($path) ){
                throw new ApiError("path must be a child of /galleries", 400);
            }
            $gallery->setVirtualPath($path);
        }
    }
    
    public function updateFile(File $file, array $data){
        if( isset($data["name"]) ){
            $file->setName($data["name"]);
        }
    }
    
}
