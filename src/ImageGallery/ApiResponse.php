<?php 
namespace ImageGallery;

interface ApiResponse{
    public function getContentType();
    public function getCode();
    public function getBody();
    public function display();
}
