<?php 
namespace ImageGallery;

class ApiView{
    
    protected $conf = null;
    protected $db = null;
    protected $browser = null;
    
    public function __construct(Config $conf, Database $db, FileBrowser $browser){
        $this->conf = $conf;
        $this->db = $db;
        $this->browser = $browser;
        $this->thumbnailer = new Thumbnailer($conf);
    }

    public function viewGallery(Gallery $gallery){
        $data = [
            "id" => $gallery->getId(),
            "path" => strval($gallery->getVirtualPath()),
            "name" => $gallery->getName(),
            "files" => [],
            "thumbnails" => [],
        ];
        foreach( $gallery->getFiles() as $file ){
            $data["files"][] = $this->viewFile($file);
        }
        foreach( $gallery->getThumbnails() as $file ){
            $data["thumbnails"][] = $this->viewFileThumbnails($file);
        }
        return $data;
    }
    
    public function viewGalleryThumbnails(File ...$files){
        $data = [ "thumbnails" => [] ];
        foreach( $files as $file ){
            $data["thumbnails"][] = $this->viewFileThumbnails($file);
        }
        return $data;
    }

    public function viewDirectory(File $parent, File ...$files){
        $data = [
            "path" => strval($parent->getVirtualPath()),
            "type" => $parent->getType(),
        ];
        $data["files"] = array_map( [$this, "viewFile"], $files);

        return $data;
    }
    
    public function viewFileThumbnails(File $file){
        $thumbs = $this->thumbnailer->allThumbs($file);
        return array_map( [$this, "viewThumbnail"], $thumbs);
    }
    
    public function viewThumbnail(Thumbnail $thumb, bool $showFile=true){
        $file = $thumb->getFile();
        $data = [
            "size_name" => $thumb->getSizeName(),
            "file_url" => $thumb->getUrl(),
            "width" => $thumb->getWidth(),
            "height" => $thumb->getHeight(),
            "aspect_ratio" => 0,
        ];
        $data["aspect_ratio"] = $this->calcAspectRatio($data["width"], $data["height"]);
        
        if( $showFile ){
            $data["file"] = [
                "id" => $file->getId(),
                "path" => strval($file->getVirtualPath()),
                "file_url" => strval($file->getUrl()),
            ];
        }
        return $data;
    }
    
    public function viewFile(File $file){
        $data = [
            "id" => $file->getId(),
            "path" => strval($file->getVirtualPath()),
            "name" => ( $file->getName() === null ? $file->getVirtualPath()->name() : $file->getName() ),
            "type" => $file->getType(),
            "file_url" => strval($file->getUrl()),
            "width" => $file->getWidth(),
            "height" => $file->getHeight(),
            "aspect_ratio" => 0,
            "thumbnails" => [],
        ];
        $data["aspect_ratio"] = $this->calcAspectRatio($data["width"], $data["height"]);
        
        foreach( $this->thumbnailer->allThumbs($file) as $thumb ){
            $data["thumbnails"][] = $this->viewThumbnail($thumb, false);
        }
        
        if( $file instanceof GalleryFile ){
            $data["gallery_id"] = $file->getGalleryId();
        }
        
        return $data;
    }
    
    public function viewFiles(File ...$files){
        $data = [
            "files" => array_map( [$this, "viewFile"], $files),
        ];
        return $data;
    }
    
    protected function calcAspectRatio(int $width, int $height){
        if( $height == 0 ){
            return 0;
        }
        return $width / $height;
    }
    
}
