<?php 
namespace ImageGallery;

class ImageFileBrowser{
    
    protected $conf = null;
    protected $virtualRoot = null;
    protected $realRoot = null;
    protected $rootUrl = null;
    
    public function __construct(Config $conf, Path $virtualRoot, Path $realRoot, URL $rootUrl){
        $this->conf = $conf;
        $this->virtualRoot = $virtualRoot;
        $this->realRoot = $realRoot;
        $this->rootUrl = $rootUrl;
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
    
    protected function checkRealPath(Path $virtualPath){
        $realFullPath = $this->realRoot->join($virtualPath);
        
        // do not allow path traversal
        if( ! $this->realRoot->isParentOf($realFullPath) ){
            throw new FileNotFoundError(strval($virtualPath));
        }
        return $realFullPath;
    }
    
    public function fileFactory(Path $virtualPath, string $type){
        // must be able to produce objects those real path is not accessible
        if( ! explode("/", $type)[0] === "image" ){
            throw new FileNotFoundError(strval($virtualFullPath));
        }
        $virtualPath = $virtualPath->resolve();
        list($virtualFullPath, $virtualPath) = $this->splitPath($virtualPath);
        $realFullPath = $this->checkRealPath($virtualPath);
        $fileUrl = strval($this->rootUrl->joinPath($virtualPath));
        return new ImageFile($virtualFullPath, $realFullPath, $fileUrl);
    }
    
    public function listFiles(Path $virtualPath){
        $virtualPath = $virtualPath->resolve();
        list($virtualFullPath, $virtualPath) = $this->splitPath($virtualPath);
        $realFullPath = $this->checkRealPath($virtualPath);
        
        if( ! $realFullPath->isdir() ){
            throw new FileNotFoundError(strval($virtualFullPath));
        }
        
        $images = [];
        foreach( new \DirectoryIterator(strval($realFullPath)) as $item ) {
            if( $item->isDot() ){
                continue;
            }
            $name = $item->getFilename();
            $fileRealPath = $realFullPath->join($name);
            $fileVirtualPath = $virtualFullPath->join($name);
            $fileUrl = strval($this->rootUrl->joinPath($virtualPath->join($name)));
            
            if( Directory::isDirectory($fileRealPath) ){
                $image = new Directory($fileVirtualPath, $fileRealPath, $fileUrl);
            }else if( ImageFile::isImage($fileRealPath) ){
                $image = new ImageFile($fileVirtualPath, $fileRealPath, $fileUrl);
            }
            
            $images[] = $image;
        }
        return $images;
    }

    public function getFile(Path $virtualPath){
        $virtualPath = $virtualPath->resolve();
        list($virtualFullPath, $virtualPath) = $this->splitPath($virtualPath);
        $realFullPath = $this->checkRealPath($virtualPath);
        $fileUrl = strval($this->rootUrl->joinPath($virtualPath));

        if( $realFullPath->isdir() ){
            return new Directory($virtualFullPath, $realFullPath, $fileUrl);
        }else if( ImageFile::isImage($realFullPath) ){
            return new ImageFile($virtualFullPath, $realFullPath, $fileUrl);
        }
        throw new FileNotFoundError(strval($virtualFullPath));
    }
    
}