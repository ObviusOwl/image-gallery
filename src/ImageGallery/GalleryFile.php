<?php 
namespace ImageGallery;

class GalleryFile extends AbstractFile implements File{
    protected $virtualPath = null;
    protected $id = null;
    protected $name = null;
    
    public function __construct(Path $virtualPath){
        $this->virtualPath = $virtualPath;
    }
    
    public function getType(){
        return "application/x.image-gallery";
    }

    public function getUrl(){
        // TODO
        return "";
    }

    public function getWidth(){
        return 0;
    }

    public function getHeight(){
        return 0;
    }
    
}