<?php
namespace ImageGallery;


class ApiError extends \Exception implements ApiResponse{
    
    protected $data = [];

    public function __construct( $message, $code = 500, Exception $previous = null ){
        parent::__construct( $message, $code, $previous );
    }
    
    public function addPayload(array $data){
        $this->data = array_merge($this->data, $data);
    }

    public function getContentType(){
        return "application/json";
    }
        
    public function getBody(){
        $data = [ "error" => $this->getMessage() ];
        $data = array_merge($this->data, $data);
        return json_encode( $data );
    }
    
    public function display(){
        http_response_code($this->getCode());
        header('Content-Type: ' . $this->getContentType());
        echo $this->getBody();
    }
    
}
