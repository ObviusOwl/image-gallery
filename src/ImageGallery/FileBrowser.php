<?php 
namespace ImageGallery;

class FileBrowser{
    
    protected $conf = null;
    protected $db = null;
    protected $fsList = [];
    
    public function __construct(Config $conf, Database $db){
        $this->conf = $conf;
        $this->db = $db;

        $imgVirtPath = new Path("/images");
        $imgRealPath = $this->conf->getPath("app.path.image_dir");
        $imgBaseUrl = $this->conf->getUrl("app.url.image_base");        
        $this->fsList[] = new ImageFileBrowser($this->conf, $imgVirtPath, $imgRealPath, $imgBaseUrl);
        
        $gaVirtPath = new Path("/galleries");
        $this->fsList[] = new GalleryFileBrowser($this->conf, $gaVirtPath);
    }

    public function getRootPath(){
        return new Path("/");
    }
    
    protected function delegateToFs(Path $virtualPath, callable $func,  ...$args){
        $virtualPath = $virtualPath->makeAbsolute()->resolve();
        foreach( $this->fsList as $fs ){
            if( $fs->getRootPath()->isParentOf($virtualPath) ){
                return call_user_func($func, $fs, $virtualPath, ...$args);
            }
        }
        throw new FileNotFoundError(strval($virtualPath));
    }
    
    public function fileFactory(Path $virtualPath, string $type){
        return $this->delegateToFs($virtualPath, function ($fs, $path) use ( $type ){
            return $fs->fileFactory($path, $type);
        } );
    }
    
    public function listFiles(Path $virtualPath){
        return $this->delegateToFs($virtualPath, function ($fs, $path){
            return $fs->listFiles($path);
        } );
    }

    public function getFile(Path $virtualPath){
        return $this->delegateToFs($virtualPath, function ($fs, $path){
            return $fs->getFile($path);
        } );
    }
    
}