<?php 
namespace ImageGallery;

class Thumbnail{
    
    protected $path = null;
    protected $file = null;
    protected $image = null;
    protected $url = null;
    protected $sizeName = null;
    
    public function __construct(Path $path, File $file, string $url, string $sizeName){
        $this->path = $path;
        $this->file = $file;
        $this->url = $url;
        $this->sizeName = $sizeName;
    }

    public function getUrl(){
        return $this->url;
    }

    public function getSizeName(){
        return $this->sizeName;
    }

    public function getFile(){
        return $this->file;
    }
    
    public function mtime(){
        $this->loadImage();
        $prop = $this->image->getImageProperty("Thumb::MTime");
        if( $prop === false || ! is_numeric($prop) ){
            return null;
        }
        return intval($prop);
    }
    
    public function getWidth(){
        try{
            $this->loadImage();
            return $this->image->getImageWidth();
        }catch( \Exception $e ){
            return 0;
        }
    }

    public function getHeight(){
        try{
            $this->loadImage();
            return $this->image->getImageHeight();
        }catch( \Exception $e ){
            return 0;
        }
    }
    
    public function exists(){
        return $this->path->exists();
    }
    
    public function isValid(){
        if( ! $this->path->exists() ){
            return false;
        }
        $thumbTime = $this->mtime();
        $fileTime = $this->file->getRealPath()->mtime();
        
        if( $thumbTime === null || $fileTime === null ){
            return false;
        }
        return $thumbTime === $fileTime;
    }
    
    public function updateProperties(){
        if( $this->image === null ){
            throw new ThumbnailError("No image loaded");
        }

        $uri = $this->file->getVirtualPath()->makeAbsolute();
        $mtime = $this->file->getRealPath()->mtime();
        if( $mtime === null ){
            throw new ThumbnailError("Failed to get mtime of file $uri");
        }
        
        $this->image->setImageProperty("Thumb::URI", strval($uri));
        $this->image->setImageProperty("Thumb::MTime", strval($mtime));
    }
    
    public function setImage(\Imagick $image){
        $oldImage = $this->image;
        try{
            $this->image = $image;
            $this->updateProperties();
        }catch( ThumbnailError $e ){
            $this->image = $oldImage;
            throw $e;
        }
    }
    
    public function getImage(){
        return $this->image;
    }
    
    public function loadImage(bool $force=false){
        if( $this->image === null || ( $this->image !== null && $force ) ){
            $this->image =  new \Imagick(strval($this->path));
        }
        return $this->image;
    }
    
    public function saveImage(){
        if( $this->image === null ){
            throw new ThumbnailError("No image loaded");
        }
        $this->writeImage($this->image);
    }
    
    public function writeImage(\Imagick $image){
        $this->setImage($image);
        $this->image->setImageFormat("png");
        if( ! $this->image->writeImage(strval($this->path)) ){
            throw new ThumbnailError("Failed to save thumbnail");
        }
    }
    
}