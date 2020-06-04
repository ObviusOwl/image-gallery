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
    
    public function viewThumbnail(Thumbnail $thumb){
        $file = $thumb->getFile();
        $data = [
            "size_name" => $thumb->getSizeName(),
            "file_url" => $thumb->getUrl(),
            "file" => [
                "id" => $file->getId(),
                "path" => strval($file->getVirtualPath()),
                "file_url" => strval($file->getUrl()),
            ],
        ];
        return $data;
    }
    
    public function viewFile(File $file){
        $data = [
            "id" => $file->getId(),
            "path" => strval($file->getVirtualPath()),
            "name" => ( $file->getName() === null ? $file->getVirtualPath()->name() : $file->getName() ),
            "type" => $file->getType(),
            "file_url" => strval($file->getUrl()),
            "thumbnails" => [],
        ];
        foreach( $this->thumbnailer->allThumbs($file) as $thumb ){
            $data["thumbnails"][] = [
                "size_name" => $thumb->getSizeName(),
                "file_url" => strval($thumb->getUrl()),
            ];
        }
        return $data;
    }
    
    public function viewFiles(File ...$files){
        $data = [
            "files" => array_map( [$this, "viewFile"], $files),
        ];
        return $data;
    }
    
}
