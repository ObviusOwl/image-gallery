<?php
/**
 * Plugin Name: imgga
 * Plugin URI: https://gitlab.terhaak.de/jojo/image-gallery
 * Description: Image gallery (external)
 * Version: 0.1
 */

class ImageGallerySettingPage{
    private $options = [];

    public $optionsName = "imgga_settings";
    public $groupName = "imgga_option_group";
    public $pageSlug = "imgga-admin";
    public $sectionId = "imgga_setting_section_id";
    
    public function __construct(){
    }

    public function wpInit(){
        add_action( 'admin_menu', array( $this, 'registerPage' ) );
        add_action( 'admin_init', array( $this, 'registerSettings' ) );
    }
    

    public function registerPage(){
        add_options_page('Gallery Settings', 'Gallery', 'manage_options', $this->pageSlug, [$this, 'pageView']);
    }

    public function registerSettings(){
        register_setting($this->groupName, $this->optionsName, [$this, 'pageCtrl']);

        add_settings_section($this->sectionId, 'Gallery Settings', [$this, 'sectionInfoView'], $this->pageSlug);

        add_settings_field( 'api_url_private', 
            'Private API URL',
            [$this, 'apiUrlPrivateView'], 
            $this->pageSlug, 
            $this->sectionId 
        );

        add_settings_field( 'api_url_public', 
            'Public API URL',
            [$this, 'apiUrlPublicView'], 
            $this->pageSlug, 
            $this->sectionId 
        );
    }

    public function pageView(){
        $this->options = get_option($this->optionsName);
        echo '<div class="wrap">'.PHP_EOL;
        echo '  <h1>Gallery Settings</h1>'.PHP_EOL;
        echo '  <form method="post" action="options.php">'.PHP_EOL;
                    settings_fields($this->groupName);
                    do_settings_sections($this->pageSlug);
                    submit_button();
        echo '  </form>'.PHP_EOL;
        echo '</div>'.PHP_EOL;
    }

    public function pageCtrl($input){
        $out = [];
        if( isset( $input['api_url_private'] ) ){
            $out['api_url_private'] = sanitize_text_field( $input['api_url_private'] );
        }
        if( isset( $input['api_url_public'] ) ){
            $out['api_url_public'] = sanitize_text_field( $input['api_url_public'] );
        }
        return $out;
    }
    
    public function apiUrlPrivateView(){
        $this->apiUrlView('api_url_private');
    }
    
    public function apiUrlPublicView(){
        $this->apiUrlView('api_url_public');
    }
    
    public function apiUrlView($name){
        $formname = "{$this->optionsName}[$name]";
        $value = isset($this->options[$name]) ? esc_attr($this->options[$name]) : '';
        echo '<input type="text" id="'.$name.'" name="'.$formname.'" value="'.$value.'" />';
    }

    public function sectionInfoView(){
    }
}

class ImageGalleryPlugin{
    
    public $postType = "image_gallery";
    public $metaBoxId = "imgga_metabox";
    public $metaKeyPrefix = "_imgga_";
    public $optionsName = "imgga_settings";
    private $options = null;
    
    public function __construct(){
    }
    
    
    /*********************************************
            getters/ setters
    **********************************************/
    
    public function getDefaultGallery($postId){
        return get_post_meta($postId, $this->$metaKeyPrefix . 'gallery_id', true);
    }
    
    public function setDefaultGallery($postId, $galleryId){
        update_post_meta($postId, $this->$metaKeyPrefix . 'gallery_id', strval($galleryId));
    }
    
    public function assetUrl($filename){
        return plugin_dir_url(__FILE__) . "assets/" . $filename;
    }
    
    public function getOpt($name, $default=null){
        if( $this->options === null ){
            $this->options = get_option($this->optionsName);
        }
        return isset($this->options[$name]) ? $this->options[$name] : $default;
    }
    
    public function fetchGallery($galleryId){
        if( is_int($galleryId) ){
            $galleryId = strval($galleryId);
        }
        if( $galleryId === "" | !is_numeric($galleryId) ){
            return null;
        }
        
        $url = $this->getOpt("api_url_private", "") . "/galleries/" . $galleryId;
        $data = file_get_contents($url);
        return $data === false ? null : json_decode($data, true);
    }

    /*********************************************
            wp register functions
    **********************************************/

    public function wpInit(){
        add_action('init', [$this, 'registerPostType']);
        
        add_action('add_meta_boxes', [$this, 'registerMetaBox']);
        add_action('save_post', [$this, 'metaBoxCtrl']);
        
        add_action('wp_head', [$this, 'registerHead']);
    }
    
    public function registerPostType(){
        register_post_type($this->postType, [
            'labels' => [
                'name'          => 'Galleries',
                'singular_name' => 'Gallery',
            ],
            'public'      => true,
            'has_archive' => false,
            'supports' => array('title','author'), 
        ]);
    }
    
    public function registerMetaBox(){
        $title = 'Image Gallery';
        add_meta_box( $this->$metaBoxId, $title, [$this, 'metaBoxView'], $this->postType);
    }
    
    public function registerHead(){
        $jsurl = $this->assetUrl("bundle.js");
        $cssurl = $this->assetUrl("bundle.css");
        $apiurl = addcslashes($this->getOpt("api_url_public", ""), '\'\\');
        
        echo "<script type='module'>".PHP_EOL;
        echo "  import {default as main, store} from '$jsurl';".PHP_EOL;
        echo "  store.setApiUrl('$apiurl');".PHP_EOL;
        echo "  main();".PHP_EOL;
        echo "</script>".PHP_EOL;
        echo "<link rel='stylesheet' href='$cssurl'>".PHP_EOL;
    }
    
    /*********************************************
            meta box form
    **********************************************/
    
    public function metaBoxView($post){
        $value = esc_attr($this->getDefaultGallery($post->ID));
        $name = $this->metaBoxId . "_def_ga_id";
        echo "<label for='$name'>Initial Gallery ID</label>";
        echo "<input type='text' name='$name' id='$name' class='postbox' value='$value' />";
    }
    
    public function metaBoxCtrl($postId){
        $name = $this->metaBoxId . "_def_ga_id";
        if( isset($_POST[$name]) && is_numeric($_POST[$name]) ){
            $this->setDefaultGallery($postId, $_POST[$name]);
        }
    }
    
}

if ( ! defined( 'ABSPATH' ) ) {
    exit;
};

$imgga = new ImageGalleryPlugin();
$imgga->wpInit();

if( is_admin() ){
    $imggaSettings = new ImageGallerySettingPage();
    $imggaSettings->wpInit();
}

function imgga_app(){
    global $imgga;
    echo "<div id='app'></div>";
    
    $galleryId = "";
    if( isset($_GET["gallery"]) && is_numeric($_GET["gallery"]) ){
        $galleryId = $_GET["gallery"];
    }
    
    if( $galleryId === "" ){
        $postId = get_the_ID();
        if( $postId !== false ){
            $galleryId = $imgga->getDefaultGallery($postId);
        }
    }
    
    if( $galleryId !== "" && is_numeric($galleryId) ){
        $gallery = $imgga->fetchGallery($galleryId);
        $jsurl = $imgga->assetUrl("bundle.js");

        echo "<script type='module'>".PHP_EOL;
        echo "  import {store} from '$jsurl';".PHP_EOL;
        if( $gallery === null ){
            echo "  store.loadGallery($galleryId);".PHP_EOL;
        }else{
            $data = addcslashes(json_encode($gallery),'\'\\');
            echo "  let gallery = JSON.parse('$data');";
            echo "  store.setGallery(gallery);".PHP_EOL;
        }
        echo "</script>".PHP_EOL;
    }
}
