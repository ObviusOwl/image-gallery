<?php 
namespace ImageGallery;

class Thumbnailer{
    const THUMBNAIL_SIZES = [
        "S" => [ 128, 128 ],
        "M" => [ 450, 300 ],
        //"L" => [ 1000, 666 ],
    ];

    protected $conf = null;
    
    public function __construct(Config $conf){
        $this->conf = $conf;
    }

    public function update(Thumbnail $thumb){
        if( ! $thumb->isValid() ){
            return $this->regenerate($thumb);
        }
        return $thumb;
    }

    public function regenerate(Thumbnail $thumb){
        $thumbs =  $this->generate($thumb->getFile(), $thumb->getSizeName());
        return $thumbs[0];
    }
    
    public function thumb(File $file, string $sizeName){
        if( $file instanceof Imagefile ){
            return $this->thumbForImage($file, $sizeName);
        }
    }
    
    public function generate(File $file, string $sizeName){
        if( $file instanceof Imagefile ){
            return $this->generateForImage($file, $sizeName);
        }
    }
    
    public function allThumbs(File $file){
        $thumbs = [];
        foreach( array_keys(Thumbnailer::THUMBNAIL_SIZES) as $sizeName ){
            $thumbs[] = $this->thumb($file, $sizeName);
        }
        return array_merge([], ...$thumbs);
    }
    
    public function generateForImage(ImageFile $file, string $sizeName){
        $baseDir = $this->getThumbnailDir($sizeName, true);
        $baseUrl = $this->conf->getUrl("app.url.thumbnail_base");
        
        $fullPath = $baseDir->join($file->thumbnailName())->withsuffix(".png");
        $fullUrl = $baseUrl->joinPath($sizeName . "/" . $fullPath->name());
        
        $size = Thumbnailer::THUMBNAIL_SIZES[$sizeName];
        $image = $file->getImage();
        
        if( $image->getImageWidth() > $size[0] || $image->getImageHeight() > $size[1] ){
            // resize only if bigger than the requested size
            $image->thumbnailImage($size[0], $size[1], true);
            $image->sharpenImage(1, 0.8);
        }
        
        $thumb = new Thumbnail($fullPath, $file, $fullUrl, $sizeName);
        $thumb->writeImage($image);
        return [ $thumb ];
    }
    
    public function thumbForImage(ImageFile $file, string $sizeName){
        $baseDir = $this->getThumbnailDir($sizeName, false);
        $baseUrl = $this->conf->getUrl("app.url.thumbnail_base");
        
        $fullPath = $baseDir->join($file->thumbnailName())->withsuffix(".png");
        $fullUrl = $baseUrl->joinPath($sizeName . "/" . $fullPath->name());
        $thumb = new Thumbnail($fullPath, $file, strval($fullUrl), $sizeName);
        
        return [ $thumb ];
    }
    
    protected function getThumbnailDir(string $sizeName, bool $makeIt=false){
        if( !isset(Thumbnailer::THUMBNAIL_SIZES[$sizeName]) ){
            throw ThumbnailError("No such thumbnail size: $sizeName");
        }
        
        $path = $this->conf->getPath("app.path.thumbnail_dir")->join($sizeName);
        
        if( $makeIt ){
            try{
                $path->makeDir(0775);
            }catch( PathError $e ){
                throw new ThumbnailError($e->getMessage());
            }
        }
        return $path;
    }
    
}