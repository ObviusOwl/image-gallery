<?php 
namespace ImageGallery;

abstract class AbstractFile{
    protected $virtualPath = null;
    protected $id = null;
    protected $name = null;
    
    protected $tainted = [];

    public function getVirtualPath(){
        return clone $this->virtualPath;
    }
    
    public function setId(?int $id){
        if( $this->id !== $id ){
            $this->tainted["id"] = true;
        }
        $this->id = $id;
    }
    
    public function getId(){
        return $this->id;
    }
    
    public function setName(?string $name){
        if( $this->name !== $name ){
            $this->tainted["name"] = true;
        }
        $this->name = $name;
    }
    
    public function getName(){
        return $this->name;
    }

    public function getTainted(){
        return $this->tainted;
    }
    
    public function clearTaint(){
        $this->tainted = [];
    }

}