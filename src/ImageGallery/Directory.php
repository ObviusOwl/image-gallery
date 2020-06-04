<?php 
namespace ImageGallery;

class Directory extends AbstractFile implements File{
    
    protected $virtualPath = null;
    protected $realPath = null;
    protected $url = null;

    public static function isDirectory(Path $path){
        return $path->isDir();
    }
    
    public function __construct(Path $virtualPath, Path $realPath, string $url){
        $this->virtualPath = $virtualPath;
        $this->realPath = $realPath;
        $this->url = $url;
    }

    public function getType(){
        return "inode/directory";
    }

    public function thumbnailName(){
        return null;
    }

    public function getRealPath(){
        return new Path($this->realPath);
    }
    
    public function getUrl(){
        return $this->url;
    }

}