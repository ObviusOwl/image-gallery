<?php 
namespace ImageGallery;

class GalleryFileBrowser{
    
    protected $conf = null;
    protected $virtualRoot = null;
    
    public function __construct(Config $conf, Path $virtualRoot){
        $this->conf = $conf;
        $this->virtualRoot = $virtualRoot;
    }

    public function getRootPath(){
        return new Path($this->virtualRoot);
    }
    
    public function fileFactory(Path $virtualPath, string $type){
        throw new FileNotFoundError( "File '$virtualPath' not found" );
    }

    public function listFiles(Path $virtualPath){
        throw new FileNotFoundError( "File '$virtualPath' not found" );
    }
    
    public function getFile(Path $virtualPath){
        throw new FileNotFoundError( "File '$virtualPath' not found" );
    }
    
}