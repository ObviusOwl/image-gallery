<?php 
namespace ImageGallery;

class Router {
    
    protected $routes = [];
    protected $defaults = [];
    
    public function route(string $verb, string $path, callable $callback){
        $verb = strtoupper($verb);
        if( ! isset($this->routes[ $verb ]) ){
            $this->routes[$verb] = [];
        }
        $this->routes[ $verb ][] = new Route($path, $callback);
    }
    
    public function default(callable $callback, string $verb = ""){
        $verb = strtoupper($verb);
        $this->defaults[ $verb ] = new Route("", $callback);
    }
    
    public function dispatch($verb=NULL, $path=NULL){
        if( $verb === null ){
            $verb = $_SERVER['REQUEST_METHOD'];
        }
        $verb = strtoupper($verb);
        if( $path === null ){
            $path = $_SERVER['PATH_INFO'];
        }

        $req = new Request();
        $req->setPath($path);
        $req->setVerb($verb);
        $req->loadBody();

        $result = null;
        $matched = false;
        // try first routes
        if( isset($this->routes[ $verb ]) ){
            foreach( $this->routes[ $verb ] as $route ){
                $matched = $route($path, $req, $result);
                if( $matched ){
                    break;
                }
            }
        }
        // then try verb default
        if( ! $matched  && isset($this->defaults[ $verb ]) ){
            $matched = $this->defaults[ $verb ]->call($req, $result);
        }
        // finally try global default
        if( ! $matched  && isset($this->defaults[ "" ]) ){
            $matched = $this->defaults[ "" ]->call($req, $result);
        }
        return $result;
    }
    
}