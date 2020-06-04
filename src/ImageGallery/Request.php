<?php 
namespace ImageGallery;


class Request{
    protected $verb = "GET";
    protected $path = null;
    protected $pathvars = [];
    protected $body = null;
    
    public function __construct(){
    }
    
    public function __get(string $name){
        $props = [ "verb", "path", "body", "pathvars" ];
        if( in_array($name, $props) ){
            return $this->$name;
        }
    }

    public function setVerb($verb){
        $this->verb = strtoupper($verb);
    }

    public function setPath($path){
        $this->path = $path;
    }

    public function setPathvars(array $vars){
        $this->pathvars = $vars;
    }
    
    public function getPathVar(string $name, $default=null){
        if( isset($this->pathvars[$name]) ){
            return $this->pathvars[$name];
        }
        return $default;
    }
    
    public function loadBody(){
        $this->body = file_get_contents('php://input');
    }

    public function setBody($data){
        $this->body = $data;
    }

    public function getBodyAsJson(){
        return json_decode($this->body, true);
    }
    
}
