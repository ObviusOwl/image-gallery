<?php 
namespace ImageGallery;

class Config implements \ArrayAccess{
    
    protected $data;

    public function offsetExists($offset){
        return isset($this->data[ $offset ]);
    }
    
    public function offsetGet($offset){
        if( isset($this->data[ $offset ]) ){
            return $this->data[ $offset ];
        }
        return null;
    }
    
    public function offsetSet($offset, $value){
        // TODO automatic casting, type checks, key validity checks
        if( $offset == null ){
            throw new ConfigError("cannot append to config object");
        }
        $this->data[ $offset ] = $value;
    }
    
    public function offsetUnset($offset){
        unset($this->data[ $offset ]);
    }
    
    public function getUrl($name){
        return new URL($this[$name]);
    }
    
    public function getPath($name){
        return new Path($this[$name]);
    }
    
}