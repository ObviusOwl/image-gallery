<?php 
namespace ImageGallery;

class URL{
    
    const PART_KEYS = array("scheme", "host", "port", "user", "pass", "path", "query", "fragment");
    protected $parts = [];
    
    public function __construct(string $url){
        $this->parse($url);
    }
    
    public function parse(string $url){
        $parts = parse_url($url);
        if( $parts === false ){
            throw new InvalidArgumentException("Malformed URL");
        }
        $this->parts = $parts;
    }
    
    public function __get($name){
        if( in_array($name, URL::PART_KEYS) && isset($this->parts[$name]) ){
            return $this->parts[$name];
        }
        return null;
    }
    
    public function __set($name, $value){
        if( ! in_array($name, URL::PART_KEYS) ){
            return null;
        }
        $this->parts[ $name ] = $value;
    }
    
    public function __isset($name){
        return isset($this->parts[$name]);
    }
    
    public function getPart(string $name, $default=null){
        if( isset($this->parts[$name]) ){
            return $this->parts[$name];
        }
        return $default;
    }
    
    public function __toString(){
        $scheme   = isset($this->parts['scheme'])   ? $this->parts['scheme'] . '://' : '';
        $host     = isset($this->parts['host'])     ? $this->parts['host']           : '';
        $port     = isset($this->parts['port'])     ? ':' . $this->parts['port']     : '';
        $user     = isset($this->parts['user'])     ? $this->parts['user']           : '';
        $pass     = isset($this->parts['pass'])     ? ':' . $this->parts['pass']     : '';
        $path     = isset($this->parts['path'])     ? $this->parts['path']           : '';
        $query    = isset($this->parts['query'])    ? '?' . $this->parts['query']    : '';
        $fragment = isset($this->parts['fragment']) ? '#' . $this->parts['fragment'] : '';
        $pass     = ($user || $pass) ? "$pass@" : '';
        return "$scheme$user$pass$host$port$path$query$fragment";
    }
    
    public function joinPath(string $path){
        $newUrl = clone $this;
        if( $path !== "" ){
            $newUrl->path = rtrim($this->getPart("path", ""), "/") . "/" . ltrim($path, "/");
        }
        return $newUrl;
    }
    
}
