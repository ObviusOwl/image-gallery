<?php 
namespace ImageGallery;

class Route{
    protected $path = null;
    protected $regex = null;
    protected $callback = null;
    
    public function __construct(string $path, callable $callback){
        $this->path = $path;
        $this->callback = $callback;
        $this->regex = $this->compile($this->path);
    }

    public function __invoke(string $path, Request $req = null, &$result = null){
        $vars = $this->match($path);
        if( $vars === null || $this->callback === null ){
            return false;
        }
        
        if( $req === null ){
            $req = new Request();
        }
        $req->setPathvars($vars);
        $req->setPath($path);
        
        return $this->call($req, $result);
    }
    
    function compile($path){
        // quote the path, so it can be used as base for the regex 
        $reg = preg_quote($path, '/');

        // replacing all occurences of ':myvar' with a named subpattern regex having the name 'myvar'
        // ':' is escaped by preg_quote, so match '\:'
        $reg = preg_replace("/\\\\:([a-zA-Z0-9_]+)/", "(?'\\1'[^\/]+)", $reg);
        if( $reg === null ){
            return null;
        }
        // match the complete path
        return "/^$reg\$/";
    }
        
    public function match(string $path){
        if( !isset($this->regex) ){
            return null;
        }
        
        $matches = [];
        $r = preg_match($this->regex, $path, $matches);
        
        if( $r === 1 ){
            $vars = [];
            // keep only named subpattern matches as they represent the path variables
            foreach( $matches as $key => $value ){
                if( ! is_int($key) ){
                    $vars[ $key ] = $value;
                }
            }
            return $vars;
        }
        
        return null;
    }
    
    public function call(Request $req, &$result = null){
        if( $this->callback === null ){
            return false;
        }
        $result = call_user_func($this->callback, $req);
        return true;
    }
    
}
