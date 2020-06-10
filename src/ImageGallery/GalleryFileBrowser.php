<?php 
namespace ImageGallery;

class GalleryFileBrowser{
    
    protected $conf = null;
    protected $db =null;
    protected $virtualRoot = null;
    
    public function __construct(Config $conf, Database $db, Path $virtualRoot){
        $this->conf = $conf;
        $this->db = $db;
        $this->virtualRoot = $virtualRoot;
    }

    public function getRootPath(){
        return new Path($this->virtualRoot);
    }

    protected function splitPath(Path $virtualPath){
        $virtualPath = $virtualPath->resolve();
        if( $virtualPath->isAbsolute() ){
            $virtualFullPath = $virtualPath;
            $virtualPath = $virtualPath->relativeTo($this->virtualRoot);
            if( $virtualPath === null ){
                throw new FileNotFoundError(strval($virtualFullPath));
            }
        }else{
            $virtualFullPath = $this->virtualRoot->join($virtualPath);
        }
        return [ $virtualFullPath, $virtualPath ];
    }
    
    public function fileFactory(Path $virtualPath, string $type){
        if( $type !== "application/x.image-gallery" ){
            throw new FileNotFoundError(strval($virtualPath));
        }
        $virtualPath = $virtualPath->resolve();
        list($virtualFullPath, $virtualPath) = $this->splitPath($virtualPath);
        
        return new GalleryFile($virtualFullPath);
    }

    public function listFiles(Path $virtualPath){
        throw new FileNotFoundError( "File '$virtualPath' not found" );
    }
    
    public function getFile(Path $virtualPath){
        throw new FileNotFoundError( "File '$virtualPath' not found" );
    }
    
}