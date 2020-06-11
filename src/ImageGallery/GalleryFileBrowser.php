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
        if( $type === "inode/directory" ){
            $virtualPath = $virtualPath->resolve();
            list($virtualFullPath, $virtualPath) = $this->splitPath($virtualPath);
            return new Directory($virtualFullPath, $virtualFullPath, "");
        }else if( $type === "application/x.image-gallery" ){
            $virtualPath = $virtualPath->resolve();
            list($virtualFullPath, $virtualPath) = $this->splitPath($virtualPath);
            return new GalleryFile($virtualFullPath);
        }
        throw new FileNotFoundError(strval($virtualPath));
    }

    public function listFiles(Path $virtualPath){
        return $this->db->listGalleryFilesByPath($virtualPath);
    }
    
    public function getFile(Path $virtualPath){
        $file = $this->db->getGalleryFileByPath($virtualPath);
        if( $file === null ){
            //throw new FileNotFoundError( "File '$virtualPath' not found" );
            return $this->fileFactory($virtualPath, "inode/directory");
        }
        return $file;
    }
    
}