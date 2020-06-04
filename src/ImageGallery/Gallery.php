<?php 
namespace ImageGallery;

class Gallery{
    protected $virtualPath = null;
    protected $id = null;
    protected $name = null;
    protected $files = [];
    protected $thumbnails = [];
    
    protected $tainted = [];
    
    
    public function __construct(){
    }
    
    public function setVirtualPath(Path $path){
        if( strval($this->virtualPath) !== strval($path) ){
            $this->tainted["virtualPath"] = true;
        }
        $this->virtualPah = $path;
    }

    public function getVirtualPath(){
        return clone $this->virtualPah;
    }
    
    public function setId(?int $id){
        if( $this->id !== $id ){
            $this->tained["id"] = true;
        }
        $this->id = $id;
    }
    
    public function getId(){
        return $this->id;
    }
    
    public function setName(string $name){
        if( $this->name !== $name ){
            $this->tainted["name"] = true;
        }
        $this->name = $name;
    }
    
    public function getName(){
        return $this->name;
    }
    
    public function getFiles(){
        return $this->files;
    }
    
    public function setFiles(File ...$files){
        $this->files = $files;
    }

    public function getThumbnails(){
        return $this->thumbnails;
    }
    
    public function setThumbnails(ImageFile ...$files){
        $this->thumbnails = $files;
    }
    
    public function getTainted(){
        return $this->tainted;
    }
    
    public function clearTaint(){
        $this->tainted = [];
    }
    
}