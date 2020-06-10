<?php 
namespace ImageGallery;

class GalleryFile extends AbstractFile implements File{
    protected $virtualPath = null;
    protected $id = null;
    protected $name = null;
    protected $galleryId = null;
    protected $gallery = null;
    
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
    
    public function setGalleryId(?int $id){
        if( $this->galleryId !== $id ){
            $this->tainted["gallery_id"] = true;
        }
        $this->galleryId = $id;
    }
    
    public function getGalleryId(){
        return $this->galleryId;
    }
    
    public function setGallery(?Gallery $ga){
        $this->gallery = $ga;
    }
    
    public function getGallery(){
        return $this->gallery;
    }
    
}