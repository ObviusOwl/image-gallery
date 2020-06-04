<?php 
namespace ImageGallery;

class ApiResponseJson implements ApiResponse{
    protected $data = [];
    protected $code = 200;
    
    public function __construct( array $data, int $code = 200 ){
        $this->data = $data;
        $this->code = $code;
    }

    public function getContentType(){
        return "application/json";
    }
    
    public function getCode(){
        return $this->code;
    }
    
    public function getBody(){
        return json_encode( $this->data );
    }
    
    public function display(){
        http_response_code($this->getCode());
        header('Content-Type: ' . $this->getContentType());
        echo $this->getBody();
    }
}
