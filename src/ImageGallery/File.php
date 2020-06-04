<?php 
namespace ImageGallery;

interface File{
    public function getType();
    
    public function setName(?string $name);
    public function getName();
    
    public function getVirtualPath();
    
    public function getUrl();
    
    public function setId(?int $id);
    public function getId();
}