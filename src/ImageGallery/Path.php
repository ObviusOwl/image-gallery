<?php 
namespace ImageGallery;

class Path{
    protected $parts = [];
    
    public function __construct($path){
        if( is_string($path) ){
            $this->parts = explode('/', $path);
        }else if( $path instanceof Path ){
            $this->parts = $path->getParts();
        }else if( is_array($path) ){
            foreach( $path as $part ){
                if( !is_string($part) ){
                    throw new PathError("All array items of the 'path' argument must be of type string");
                }
            }
            $this->parts = $path;
        }else{
            throw new PathError("Argument 'path' must be string, Path or array type");
        }
    }
    
    /*
        methods acting on the path: naive string processing 
    */
    
    public function __toString(){
        return implode('/', $this->parts);
    }
    
    public function getParts(){
        return $this->parts;
    }
    
    public function join(...$paths){
        $finalParts = $this->isAbsolute() ? [""] : [];
        // append paths to $this path
        array_unshift($paths, $this);
        
        // filter out empty parts: trailing slashes, but also in the middle of the paths
        foreach( $paths as $path ){
            $parts = ( new Path($path) )->getParts();
            $parts = array_filter($parts, function ($p){ return $p !== ""; });
            array_push($finalParts, ...$parts);
        }
        return new Path($finalParts);
    }
    
    public function isAbsolute(){
        return isset($this->parts[0]) && $this->parts[0] == "";
    }
    
    public function parent(){
        $index = $this->getLastPart();
        if( $index > 0 ){
            return new Path(array_slice($this->parts, 0, $index));
        }
        return new Path("");
    }
    
    public function name(){
        $index = $this->getLastPart();
        if( $index > 0 ){
            return $this->parts[ $index ];
        }
        return "";
    }
    
    public function resolve(){
        $finalParts = $this->isAbsolute() ? [""] : [];

        foreach( $this->parts as $part ){
            if( $part != ".." && $part != "." && $part != "" ){
                $finalParts[] = $part;
            }else if( $part == ".." ){
                $p = array_pop( $finalParts );
                if( $p === "" && sizeof($finalParts) == 0 ){
                    $finalParts[] = "";
                }
            }
        }
        
        return new Path($finalParts);
    }
    
    public function splitSuffix(){
        $index = $this->getLastPart();
        if( $index < 0 ){
            return [ new Path($this), "", "" ];
        }

        $parts = array_slice($this->parts, 0, $index);
        $nameParts = explode('.', $this->parts[ $index ]);
        
        if( sizeof($nameParts) == 1 ){
            $suffix = "";
            $name = $nameParts[0];
        }else{
            $suffix = "." . array_pop($nameParts);
            $name = implode('.', $nameParts);
        }

        return [ new Path($parts), $name, $suffix ];
    }
    
    public function withSuffix(string $suffix){
        [$path, $name, $oldSuffix] = $this->splitSuffix();
        $parts = $path->getParts();
        $parts[] = $name . $suffix;
        return new Path($parts);
    }
    
    public function relativeTo(Path $path){
        $p1 = $this->resolve()->getParts();
        $p2 = $path->resolve()->getParts();
        
        if( sizeof($p1) < sizeof($p2) ){
            return null;
        }else if( sizeof($p1) == sizeof($p2) ){
            return new Path('.');
        }
        
        $parts = [];
        for( $i=0; $i < sizeof($p2); $i++ ){
            if( $p1[ $i ] !== $p2[ $i ] ){
                return null;
            }
        }
        return new Path(array_slice($p1, sizeof($p2)));
    }
    
    public function isParentOf(Path $path){
        return $path->relativeTo($this) !== null;
    }
    
    public function makeAbsolute(Path $base = null){
        if( $base !== null ){
            if( $base->isAbsolute() ){
                $p = $base;
            }else{
                $p = ( new Path("/") )->join($base);
            }
        }else{
            $p = new Path("/");
        }
        return $p->join($this);
    }

    /*
        methods acting on the file system: interpreting path as a real (existing) file
    */

    public function isFile(){
        return is_file(strval($this));
    }
    
    public function isDir(){
        return is_dir(strval($this));
    }
    
    public function isWritable(){
        return is_writable(strval($this));
    }
    
    public function isReadable(){
        return is_readable(strval($this));
    }
    
    public function exists(){
        return file_exists(strval($this));
    }

    public function makeDir(int $mode=0777, bool $strict=false){
        if( $this->isDir() ){
            if( $strict ){
                throw new PathError("Failed to create dir: '$this' exists");
            }
            return $this;
        }
        if( ! mkdir(strval($this), $mode, true) ){
            $err = error_get_last()["message"];
            throw new PathError("Failed to create dir '$this': $err");
        }
        return $this;
    }
    
    public function realpath(){
        $path = realpath(strval($this));
        if( $path === false ){
            return null;
        }
        return new Path($path);
    }
    
    public function isSame(Path $path){
        $p1 = realpath(strval($this));
        $p2 = realpath(strval($path));
        return ( $pi === $p2 && $p1 !== false );
    }
    
    public function mtime(){
        $t = filemtime(strval($this));
        return $t === false ? null : $t;
    }
    
    public function mimeType(){
        $type = mime_content_type(strval($this));
        if( $type === false ){
            return null;
        }
        return $type;
    }

    /*
        Private helper methods
    */
    
    private function getLastPart(){
        for( $i=sizeof($this->parts)-1; $i >= 0; $i-- ){
            if( $this->parts[ $i ] !== "" ){
                return $i;
            }
        }
        return -1;
    }

}