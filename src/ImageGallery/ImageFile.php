<?php 
namespace ImageGallery;

class ImageFile extends AbstractFile implements File{
    protected $virtualPath = null;
    protected $realPath = null;
    protected $type = null;
    protected $url = null;
    protected $image = null;
    
    public static function isImage(Path $path){
        $type = null;
        if( $path->exists() ){
            $type = $path->mimeType();
        }
        if( $type === null ){
            return false;
        }
        return ( explode("/", $type)[0] === "image" );
    }
    
    public function __construct(Path $virtualPath, Path $realPath, string $url){
        $this->virtualPath = $virtualPath;
        $this->realPath = $realPath;
        $this->url = $url;
    }
    
    public function getType(){
        if( $this->type !== null ){
            return $this->type;
        }
        $type = null;
        if( $this->realPath->exists() ){
            $type = $this->realPath->mimeType();
        }
        $this->setType($type);
        return $this->type;
    }
    
    public function setType(string $type){
        if( explode("/", $type)[0] !== "image" ){
            throw new FileBrowserError("not an image type");
        }
        if( $this->type !== $type ){
            $this->tainted["type"] = true;
        }
        $this->type = $type;
    }
    
    public function thumbnailName(){
        $path = $this->virtualPath->makeAbsolute();
        return sha1($path);
    }
    
    public function getRealPath(){
        return new Path($this->realPath);
    }
    
    public function getUrl(){
        return $this->url;
    }
    
    public function getImage(){
        if( $this->image === null ){
            $this->image = new \Imagick(strval($this->realPath));
        }
        return clone $this->image;
    }
    
}
